<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Console;

use Doctrine\DBAL\DBALException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Entry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportRole;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\UserInstance;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Symfony\Component\Console\Input\ArrayInput as ConsoleOptions;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;


class SynchronizeLdapUsers extends Command
{
    CONST CLS = "\033[H"; // Clear screen
    CONST CLL = "\033[K"; // Clear line
    CONST MCH = "\033[2J"; // Move cursor home
    CONST MCA = "\033[1A"; // Move cursor up one point

    private $container;
    private $questionHelper;
    private $ldapConfig;
    private $databaseConfig;
    private $entityManager;
    private $ldap;


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('uvdesk_core:ldap:sync-users')
            ->setDescription('Synchronizes Ldap users with database')
            ->setHelp('This command allows you to synchronize your ldap server users with helpdesk database.')
            ->setHidden(false);
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {   
        $this->consoleInput = $input;
        $this->consoleOutput = $output;
        $this->questionHelper = $this->getHelper('question');

        // Ldap Connection
        $this->ldapConfig['connection'] = [
            'host' => $this->container->getParameter('uvdesk.ldap.connection.host'),
            'port' => $this->container->getParameter('uvdesk.ldap.connection.port'),
            'encryption' => $this->container->getParameter('uvdesk.ldap.connection.encryption'),
            'options' => $this->container->getParameter('uvdesk.ldap.connection.options'),
        ];
        $this->ldap = Ldap::create('ext_ldap', $this->ldapConfig['connection']);
    }

    

    protected function isLdapConfigurationValid(LdapInterface $ldap, string $search_dn, string $search_password)
    {   
        try {
            $ldap->bind($search_dn, $search_password);
        } catch(ConnectionException $e) {
            // @TODO: Log errors to log file for debugging
            return false;
        }
    }

    protected function isDatabaseConfigurationValid(EntityManagerInterface $entityManager)
    {   
        $databaseConnection = $entityManager->getConnection();
        if (false === $databaseConnection->isConnected()) {
            try {    
                $databaseConnection->connect();
            } catch (DBALException $e) {
                return false;
            }
        }

        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write([self::MCH, self::CLS]);
        $output->writeln("\n<comment>  Examining existing Database Configuration:</comment>\n");
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $db_name = $entityManager->getConnection()->getDatabase();
        if (false === $this->isDatabaseConfigurationValid($entityManager)) {
            $output->writeln([
                "  <fg=red;>[x]</> Invalid Database configuration.",
                "\n  Exiting evaluation process.\n",
            ]);
            // @TODO: Reconfigure database
            return;
        } else {
            $output->writeln("  <info>[v]</info> Successfully established a connection with database <info>$db_name</info>\n");
        }
        
        $output->writeln("\n<comment>  Examining existing Ldap Configuration:</comment>\n");  
        $base_dn = $this->container->getParameter('uvdesk.ldap.base_dn');
        $search_dn = $this->container->getParameter('uvdesk.ldap.search_dn');
        $search_password = $this->container->getParameter('uvdesk.ldap.search_password');
        
        if (false === $this->isLdapConfigurationValid($this->ldap, $search_dn, $search_password)) {
            $output->writeln([
                "  <fg=red;>[x]</> Invalid Ldap configuration.",
                "\n  Exiting evaluation process.\n",
            ]);
            // @TODO: Reconfigure Ldap
            return;
        } else {
            $output->writeln("  <info>[v]</info> Successfully established a connection with Ldap server <info>$base_dn</info>\n");
        }
        $parent_dn = $this->askInteractiveQuestion("<comment>Please enter user's parent entry DN</comment>: ", null, 6, true, false, "Please enter a valid DN");
        $email_attr = $this->askInteractiveQuestion("<comment>User's email attribute name (defaults to mail)</comment>: ", 'mail', 6, false, false, "Please enter a valid attribute name");

        $autoGenerateName = false;
        $output->write([self::MCA, self::CLL, "\n"]);
        $interactiveQuestion = new Question("      <comment>Do you want to have name auto-generated from email? [Y/N]</comment> ", 'Y');
        if ('Y' === strtoupper($this->questionHelper->ask($input, $output, $interactiveQuestion))) {
            $autoGenerateName = true;
            $output->write([self::MCA, self::CLL]);
        } else {
            $output->write([self::MCA, self::CLL]);
            $name_attr = $this->askInteractiveQuestion("<comment>User's name attribute (defaults to cn)</comment>: ", 'cn', 6, false, false, "Please enter a valid attribute name");        
        }
        
        $password_attr = $this->askInteractiveQuestion("<comment>User's password attribute name (defaults to userPassword)</comment>: ", 'userPassword', 6, false, false, "Please enter a valid attribute name");

        $roles = ['ROLE_AGENT', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_CUSTOMER'];
        $role = $this->askChoiceQuestion("<comment>Please selects the role (defaults to ROLE_AGENT)</comment>:", $roles, 0, 6, false);
        $role = $entityManager->getRepository("UVDeskCoreFrameworkBundle:SupportRole")->findOneByCode($role);
        
        $output->write([self::MCA, self::CLL]);
        $sync_types = ['Mass', 'Single User Configuration'];
        $sync_type = $this->askChoiceQuestion('<comment>Please selects the type of synchronization (defaults to Mass)</comment>:', $sync_types, 0, 6, false, "You have selected <info>{option}</info> option.");

        $output->write([self::MCA, self::CLL]); 
        if ("mass" !== strtolower($sync_type)) {
            $email = $this->askInteractiveQuestion("<comment>Email</comment>: ", '', 6, false, false, "Please enter a valid email address");
        } else {
            $email = '*';
        }

        try {
            $this->ldap->bind($search_dn, $search_password);
            $query = "($email_attr={email})";
            if (strpos($parent_dn, $base_dn) === false) {
                $base_dn = !empty($parent_dn) ? ("$parent_dn,". $base_dn) : $base_dn;
            }
            $query = str_replace("{email}", $email, $query);
            $search = $this->ldap->query($base_dn, $query);
        } catch (ConnectionException $e) {
            throw new LdapException('Could not connect to ldap server');
        }

        $entries = $search->execute()->toArray();
        $count = \count($entries);
        if (!$count) {
            $output->writeln(["      No User Found.", ""]);
        }
        if ( $email !== "*" && ($count > 1) ) {
            $output->writeln(["      More than one user found.", ""]);
        }

        foreach($entries as $entry) {
            try {
                $email = $this->getAttributeValue($entry, $email_attr);
                $user = $entityManager->getRepository("UVDeskCoreFrameworkBundle:User")->findOneByEmail($email);
                
                if (!empty($user)) {
                    $output->writeln( "      <comment>User</comment>:  " . $email . " exists already.\n             Skipping....\n"); 
                    continue;
                }
                $user = new CoreEntities\User;
                $password = $this->getAttributeValue($entry, $password_attr);
                
                $user->setEmail($email);
                $user->setPassword($password);

                if ($autoGenerateName) {
                    $name = ucwords(current(explode("@", $email)));
                    $names = [$name];
                } else {
                    $name = $this->getAttributeValue($entry, $email_attr);
                    $names = explode(" ", $name, 2);
                }
                $user->setFirstName($names[0]);
                $user->setLastName(isset($names[1]) ? $names[0] : " ");
                $user->setIsEnabled(true);
                $entityManager->persist($user);
                $entityManager->flush();

                $userInstance = new CoreEntities\UserInstance;
                $userInstance->setSource('website');
                $userInstance->setIsActive(true);
                $userInstance->setIsVerified(true);
                $userInstance->setUser($user);
                $userInstance->setSupportRole($role);

                $entityManager->persist($userInstance);
                $entityManager->flush();
                $output->writeln( "      <comment>User</comment>:  " . $email . " created successfully.\n"); 

            } catch(\Exception $e) {
                $message = "      <comment>User</comment>:  " . $email . "\n" . 
                           "      <comment>Error</comment>: " . $e->getMessage() . "\n"; 
                $output->writeln($message);
                continue;
            }
        }
        
        $output->writeln(["      Synchronization Process Completed.", ""]);
    }

    protected function askInteractiveQuestion($question, $default, int $indentLength = 6, bool $nullable = true, bool $secure = false, $warningMessage = "")
    {
        $flag = false;
        $indent = str_repeat(' ', $indentLength);
        
        do {
            $prompt = new Question($indent . $question, $default);
            // Hide user input
            if (true == $secure) {
                $prompt->setHidden(true);
                $prompt->setHiddenFallback(false);
            }

            $input = $this->questionHelper->ask($this->consoleInput, $this->consoleOutput, $prompt);
            $this->consoleOutput->write(false == $flag ? [self::MCA, self::CLL] : [self::MCA, self::CLL, self::MCA, self::CLL]);
            if (empty($input) && false == $nullable && empty($default)) {
                if (!empty($default)) {
                    $input = $default;
                } else if (false == $nullable) {
                    $flag = true;
                    $this->consoleOutput->writeln("$indent<comment>Warning</comment>: " . ($warningMessage ?? "Please enter a valid value"));
                }
            }
        } while (empty($input) && false == $nullable);

        return $input ?? null;
    }

    protected function askChoiceQuestion($question, array $choices, int $defaultIndex = 0, int $indentLength = 6, bool $nullable = true, string $successMessageTemplate = "You have selected <info>{option}</info> option.")
    {
        $flag = false;
        $indent = str_repeat(' ', $indentLength);
        $choicesCount = count($choices);
        foreach($choices as $index => $choice) {
            $formattedChoices[] = ($indent . "  [<info>$index</info>] " . "$choice");
        }
        $formattedChoices[] = " ";

        do {    
            
            $choicesString = implode("\n", $formattedChoices);
            $prompt = new Question($indent . $question . "\n$choicesString\n$indent", $defaultIndex);
            $input  = $this->questionHelper->ask($this->consoleInput, $this->consoleOutput, $prompt);            
            $this->consoleOutput->write([self::MCA, self::CLL]);

            if (!is_numeric($input) || (is_numeric($input) && ($input < 0) && ($input >= $choicesCount) && false == $nullable ) ) { 
                $formattedChoices[$choicesCount] = "$indent<bg=red;>Type $input is Invalid</>";
            } elseif ($nullable || (is_numeric($input) && ($input >= 0) && ($input < $choicesCount)) ) {
                if ($nullable && !(is_numeric($input) && ($input >= 0) && ($input < $choicesCount))) {
                    $input = $defaultIndex;
                }
                if ($formattedChoices[$choicesCount] !== " ") {
                    $this->consoleOutput->write([self::MCA, self::CLL]);
                }
                $this->consoleOutput->writeln($indent.str_replace('{option}', $choices[$input], $successMessageTemplate));
            }
            foreach(range(0, $choicesCount + 1) as $i) {
                $this->consoleOutput->write([self::MCA, self::CLL]);
            }
            
        } while ( !is_numeric($input) || ( false == $nullable && (is_numeric($input) && ($input < 0) && ($input >= $choicesCount)) ) );

        return $choices[$input] ?? null;
    }

    private function reConfigureLdap() : ?LdapInterface
    {   
        // @TODO: Reconfigure Ldap;
        return null;
    }

    private function reConfigureDatabase(): ?EntityManagerInterface {
        
        // @TODO: Reconfigure database;
        return null;
    }

    private function getAttributeValue(Entry $entry, $attribute)
    {
        if (!$entry->hasAttribute($attribute)) {
            throw new InvalidArgumentException(sprintf('Missing attribute "%s" for user "%s".', $attribute, $entry->getDn()));
        }

        $values = $entry->getAttribute($attribute);

        if (1 !== \count($values)) {
            throw new InvalidArgumentException(sprintf('Attribute "%s" has multiple values.', $attribute));
        }

        return $values[0];
    }
}

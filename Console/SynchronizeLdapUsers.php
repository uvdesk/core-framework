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
use Symfony\Component\DependencyInjection\ContainerInterface;
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


    public function __construct(ContainerInterface $container, EntityManagerInterface $entityManager )
    {
        $this->container = $container;
        $this->entityManager = $entityManager;
        
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

    protected function isDatabaseConfigurationValid(EntityMnagerInterface $entityManager)
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
        $output->writeln("\n<comment>  Examining existing Ldap Configuration:</comment>\n");  
        
        $base_dn = $this->container->getParameter('uvdesk.ldap.base_dn');
        $search_dn = $this->container->getParameter('uvdesk.ldap.search_dn');
        $search_password = $this->container->getParameter('uvdesk.ldap.search_password');
        
        if (false === $this->isLdapConfigurationValid($this->ldap, $search_dn, $search_password)) {
            $this->reConfigureLdap();
        } else {
            $output->writeln("  <info>[v]</info> Successfully established a connection with Ldap server <info>$base_dn</info>\n");
        }

        $output->write([self::MCH, self::CLS]);
        $output->writeln("\n<comment>  Examining existing Ldap Configuration:</comment>\n");

        $entityManager = $this->container->get('entityManager');
        $db_name = $entityManager->getConnection()->getDatabase();

        if (false === $this->isDatabaseConfigurationValid($entityManager)) {
            $this-> reConfigureDatabase();
        } else {
            $output->writeln("  <info>[v]</info> Successfully established a connection with database <info>$database</info>\n");
        }

        $parent_rdn = $this->askInteractiveQuestion("<info>User's parent entry RDN attribute name</info>: ", 'cn', 6, false, false, "Please enter a valid attribute name");
        $parent_rdn_value = $this->askInteractiveQuestion("<info>User's parent entry RDN attribute value</info>: ", '', 6, false, false, "Please enter a valid attribute value");
        
        $choiceQuestion = new ChoiceQuestion(
            'Please selects the type of synchronization (defaults to mass)',
            ['Mass', 'Single User Configuration'],
            0
        );
        $choiceQuestion->setErrorMessage('Type %s is invalid.');
        $sync_type = $this->questionHelper->ask($input, $output, $choiceQuestion);

        $email_attr = $this->askInteractiveQuestion("<info>User's email attribute name (defaults to mail)</info>: ", 'mail', 6, false, false, "Please enter a valid attribute name");
        
        $autoGenerateName = false;
        $interactiveQuestion = new Question("\n      <comment>Do you want to have name auto-generated from email? [Y/N]</comment> ", 'Y');
        if ('Y' === strtoupper($this->questionHelper->ask($input, $output, $interactiveQuestion))) {
            $autoGenerateName = true;
        }
        $name_attr = $this->askInteractiveQuestion("<info>User's name attribute </info>: ", 'cn', 6, false, false, "Please enter a valid attribute name");
        
        $encode_password = false;
        $password_attr = $this->askInteractiveQuestion("<info>User's password attribute name</info>: ", 'userPassword', 6, false, false, "Please enter a valid attribute name");
        $interactiveQuestion = new Question("\n      <comment>Do you want to encode password field? [Y/N]</comment> ", 'Y');
        if ('Y' === strtoupper($this->questionHelper->ask($input, $output, $interactiveQuestion))) {
            $encode_password = true;
        }

        $choiceQuestion = new ChoiceQuestion(
            'Please selects the role (defaults to ROLE_AGENT)',
            ['ROLE_AGENT', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_CUSTOMER'],
            0
        );
        $choiceQuestion->setErrorMessage('Role %s is invalid.');
        $role = $this->questionHelper->ask($input, $output, $choiceQuestion);
        
        if ("mass" !== strtolower($sync_type)) {
            $email = $this->askInteractiveQuestion("<info>Email</info>: ", '', 6, false, false, "Please enter a valid email address");
        }

        try {
            $this->ldap->bind($this->searchDn, $this->searchPassword);
            $username = $this->ldap->escape($username, '', LdapInterface::ESCAPE_FILTER);
            $query = "($parent_rdn=$parent_rdn_value," . $base_dn.")";
            if (!empty($email)) {
                $filter_dn = "(&($email_attr=$email)$query)";
            }
            $search = $this->ldap->query($this->baseDn, $query, ['scope' => QueryInterface::ONE]);
        } catch (ConnectionException $e) {
            throw new LdapException('Could not connect to ldap server');
        }

        $entries = $search->execute();
        $count = \count($entries);

        if (!$count) {
            throw new LdapException('No user found.');
        }

        if ( !empty($email) && ($count > 1) ) {
            throw new UsernameNotFoundException('More than one user found');
        }

        foreach($entries as $entry) {
            $email = $this->getAttributeValue($entry, $email_attr);
            $user = $entityManager->getRepository("UVDeskCoreFrameworkBundle:User")->findOneByEmail
            $password = $this->getAttributeValue($entry, $password_attr);
            
            if ($autoGenerateName) {
                $name = ucwords(current(explode("@", $email)));
            } else {
                $name = $this->getAttributeValue($entry, $email_attr);
                $names = explode(" ", $name, 2);
                
            } 
            
        }

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

    private function reConfigureLdap() : ?LdapInterface
    {
        return ;
    }

    private function reConfigureDatabase(): ?EntityManagerInterface {
        return ;
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

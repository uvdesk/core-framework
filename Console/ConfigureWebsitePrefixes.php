<?php

namespace Webkul\UVDesk\CoreBundle\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigureWebsitePrefixes extends Command
{
    private $io;
    private $container;
    private $questionHelper;
    private $websitePrefixRegex;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('uvdesk:configure-prefixes');
        $this->setDescription('Scans through your helpdesk setup to check for any mis-configurations.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->questionHelper = $this->getHelper('question');
    }

    /**
     * @TODO: Enable this command only on development mode.
     * @TODO: Clear Cache.
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Website Configuration');

        $this->websitePrefixRegex = '/^[a-z0-9A-Z]+$/';

        $member_panel_prefix = $this->promtAdminPanelPrefix($input, $output);
        $knowledgebase_prefix = $this->promtknowledgebasePrefix($input, $output);
        $result = $this->updateWebsitePrefixes($member_panel_prefix, $knowledgebase_prefix);

        $output->writeln("\n<info>Congrats! Your website prefixes has been updated.</info>");
        $output->writeln("\n<comment>Note: </comment>");
        $output->writeln("<comment>Updated Member Panel URL: </comment>" . $result['memberLogin']);
        $output->writeln("<comment>Updated Knowledgebase URL: </comment>" . $result['knowledgebase']);
    }

    private function promtAdminPanelPrefix(InputInterface $input, OutputInterface $output)
    {
        $memberPanelQuestion = new Question("      <question>Enter Member Panel Prefix:</question>");
        
        do {
            $this->io->section('Admin Panel');
            $memberPanelPrefix = $this->questionHelper->ask($input, $output, $memberPanelQuestion);

            $isMemberPanelPattern = preg_match($this->websitePrefixRegex, $memberPanelPrefix);
            if (!$isMemberPanelPattern) {
                $output->writeln("      <error>Warning</error>: prefix pattern do not match.\n");
            }
        } while (!$memberPanelPrefix);

        return $memberPanelPrefix;
    }
    
    private function promtknowledgebasePrefix(InputInterface $input, OutputInterface $output)
    {
        $knowledgebaseQuestion = new Question("      <question>Enter Knowledgebase Panel Prefix:</question>");
        
        do {
            $this->io->section('knowledgebase Panel');
            $knowledgebasePanelPrefix = $this->questionHelper->ask($input, $output, $knowledgebaseQuestion);

            $isKnowledgebasePattern = preg_match($this->websitePrefixRegex, $knowledgebasePanelPrefix);
            if (!$isKnowledgebasePattern) {
                $output->writeln("      <error>Warning</error>: prefix pattern do not match.\n");
            }
        } while (!$knowledgebasePanelPrefix);

        return $knowledgebasePanelPrefix;
    }

    public function updateWebsitePrefixes($member_panel_prefix, $knowledgebase_prefix)
    {
        $website_prefixes = [
            'member_prefix' => $member_panel_prefix,
            'customer_prefix' => $knowledgebase_prefix,
        ];

        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk.yaml';
        
        // get file content and index
        $file = file($filePath);
        foreach ($file as $index => $content) {
            if (false !== strpos($content, 'uvdesk_site_path.member_prefix')) {
                list($member_panel_line, $member_panel_text) = array($index, $content);
            }

            if (false !== strpos($content, 'uvdesk_site_path.knowledgebase_customer_prefix')) {
                list($customer_panel_line, $customer_panel_text) = array($index, $content);
            }
        }

        // save updated data in a variable ($updatedFileContent)
        $updatedFileContent = $file;

        // get old member-prefix
        $oldMemberPrefix = substr($member_panel_text, strpos($member_panel_text, 'uvdesk_site_path.member_prefix') + strlen('uvdesk_site_path.member_prefix: '));
        $oldMemberPrefix = preg_replace('/([\r\n\t])/','', $oldMemberPrefix);

        $updatedPrefixForMember = (null !== $member_panel_line) ? substr($member_panel_text, 0, strpos($member_panel_text, 'uvdesk_site_path.member_prefix') + strlen('uvdesk_site_path.member_prefix: ')) . $website_prefixes['member_prefix'] . PHP_EOL: '';
        $updatedPrefixForCustomer = (null !== $customer_panel_line) ? substr($customer_panel_text, 0, strpos($customer_panel_text, 'uvdesk_site_path.knowledgebase_customer_prefix') + strlen('uvdesk_site_path.knowledgebase_customer_prefix: ')) . $website_prefixes['customer_prefix'] . PHP_EOL : '';

        $updatedFileContent[$member_panel_line] = $updatedPrefixForMember;
        $updatedFileContent[$customer_panel_line] = $updatedPrefixForCustomer;

        // flush updated content in file
        file_put_contents($filePath, $updatedFileContent);

        $router = $this->container->get('router');
        $knowledgebaseURL = $router->generate('helpdesk_knowledgebase');
        $memberLoginURL = $router->generate('helpdesk_member_handle_login');
        $memberLoginURL = str_replace($oldMemberPrefix, $website_prefixes['member_prefix'], $memberLoginURL);

        return $collectionURL = [
            'memberLogin' => $memberLoginURL,
            'knowledgebase' => $knowledgebaseURL,
        ];
        
    }
}
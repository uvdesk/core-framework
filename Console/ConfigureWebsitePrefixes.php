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
    const REGEX_WEBSITE_PREFIX = '/^[a-z0-9A-Z]+$/';

    private $io;
    private $container;
    private $questionHelper;

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

        $currentWebsitePrefixes = $this->container->get('uvdesk.service')->getCurrentWebsitePrefixes();
        $currentMemberPanelPrefix = $currentWebsitePrefixes['memberPrefix'];
        $currentknowledgebasePrefix = $currentWebsitePrefixes['knowledgebasePrefix'];

        $member_panel_prefix = $this->promptAdminPanelPrefix($input, $output, $currentMemberPanelPrefix);
        $knowledgebase_prefix = $this->promptknowledgebasePrefix($input, $output, $member_panel_prefix, $currentknowledgebasePrefix);
        $result = $this->container->get('uvdesk.service')->updateWebsitePrefixes($member_panel_prefix, $knowledgebase_prefix);

        $output->writeln("\n<info>Congrats! Your website prefixes has been updated.</info>");
        $output->writeln("\n<comment>Note: </comment>");
        $output->writeln("<comment>Updated Member Panel URL: </comment>" . $result['memberLogin']);
        $output->writeln("<comment>Updated Knowledgebase URL: </comment>" . $result['knowledgebase']);
    }

    private function promptAdminPanelPrefix(InputInterface $input, OutputInterface $output, $currentPrefix)
    {
        $memberPanelQuestion = new Question("      <question>Enter Member Panel Prefix</question>( current prefix => " . $currentPrefix . " ): ");
        
        do {
            $this->io->section('Admin Panel');
            $memberPanelPrefix = $this->questionHelper->ask($input, $output, $memberPanelQuestion);

            $isMemberPanelPattern = preg_match(self::REGEX_WEBSITE_PREFIX, $memberPanelPrefix);
            if (!$isMemberPanelPattern) {
                $output->writeln("      <error>Warning</error>: prefix pattern do not match.\n");
            }
        } while (!$isMemberPanelPattern);

        return $memberPanelPrefix;
    }
    
    private function promptknowledgebasePrefix(InputInterface $input, OutputInterface $output, $memberPanelPrefix, $currentKnowledgebasePrefix)
    {
        $knowledgebaseQuestion = new Question("      <question>Enter Knowledgebase Panel Prefix</question>( current prefix => " . $currentKnowledgebasePrefix . " ): ");
        
        do {
            $this->io->section('knowledgebase Panel');
            $knowledgebasePanelPrefix = $this->questionHelper->ask($input, $output, $knowledgebaseQuestion);

            $isKnowledgebasePattern = preg_match(self::REGEX_WEBSITE_PREFIX, $knowledgebasePanelPrefix);

            if (!$isKnowledgebasePattern) {
                $output->writeln("      <error>Warning</error>: prefix pattern do not match.\n");
            } else if ($knowledgebasePanelPrefix == $memberPanelPrefix) {
                $isKnowledgebasePattern = 0;
                $output->writeln("      <error>Warning</error>: prefix of knowledgebase website can not be the same as prefix of member website.\n");
            }
        } while (!$isKnowledgebasePattern);

        return $knowledgebasePanelPrefix;
    }
}
<?php

namespace Webkul\UVDesk\CoreBundle\Console;

use Doctrine\ORM\EntityManager;
use Webkul\UVDesk\CoreBundle\Entity\Mailbox;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RefreshMailboxCommand extends Command
{
    private $container;
    private $entityManager;

    public function __construct(ContainerInterface $container, EntityManager $entityManager)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('uvdesk:refresh-mailbox');
        $this->setDescription('Check if any new emails have been received and process them into tickets');

        $this->addArgument('emails', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, "Email address of the mailboxes you wish to update");
        $this->addOption('timestamp', 't', InputOption::VALUE_REQUIRED, "Fetch messages no older than the given timestamp");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Sanitize emails
        $mailboxEmailCollection = array_map(function ($email) {
            return filter_var($email, FILTER_SANITIZE_EMAIL);
        }, $input->getArgument('emails'));
        
        // Stop execution if no valid emails have been specified
        if (empty($mailboxEmailCollection)) {
            if (false === $input->getOption('no-interaction')) {
                $output->writeln("\n <comment>No valid mailbox emails specified.</comment>\n");
            }

            return;
        }

        // Process mailboxes
        $timestamp = new \DateTime(sprintf("-%u minutes", (int) ($input->getOption('timestamp') ?: 1440)));

        foreach ($mailboxEmailCollection as $mailboxEmail) {
            try {
                $mailbox = $this->container->get('uvdesk.core.mailbox')->getMailboxByEmail($mailboxEmail);

                if (false == $mailbox['enabled']) {
                    if (false === $input->getOption('no-interaction')) {
                        $output->writeln("\n <comment>Mailbox for email </comment><info>$mailboxEmail</info><comment> is not enabled.</comment>\n");
                    }
    
                    continue;
                } else if (empty($mailbox['imap_server'])) {
                    if (false === $input->getOption('no-interaction')) {
                        $output->writeln("\n <comment>No imap configurations defined for email </comment><info>$mailboxEmail</info><comment>.</comment>\n");
                    }
    
                    continue;
                }
            } catch (\Exception $e) {
                if (false == $input->getOption('no-interaction')) {
                    $output->writeln("\n <comment>Mailbox for email </comment><info>$mailboxEmail</info><comment> not found.</comment>\n");
                }

                continue;
            }

            $this->refreshMailbox($mailbox['imap_server']['host'], $mailbox['imap_server']['username'], $mailbox['imap_server']['password'], $timestamp);
        }
    }

    public function refreshMailbox($server_host, $server_username, $server_password, \DateTime $timestamp)
    {
        $imap = imap_open($server_host, $server_username, $server_password);

        if ($imap) {
            $emailCollection = imap_search($imap, 'SINCE "' . $timestamp->format('d F Y') . '"');

            if (is_array($emailCollection)) {
                foreach ($emailCollection as $id => $messageNumber) {
                    $message = imap_fetchbody($imap, $messageNumber, "");
                    $this->pushMessage($message);
                }
            }
        }
        
        return;
    }

    public function pushMessage($message)
    {
        $router = $this->container->get('router');
        $router->getContext()->setHost($this->container->getParameter('uvdesk.site_url'));

        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_POST, 1);
        curl_setopt($curlHandler, CURLOPT_URL, $router->generate('helpdesk_member_mailbox_notification', [], UrlGeneratorInterface::ABSOLUTE_URL));
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, http_build_query(['email' => $message]));
        $curlResponse = curl_exec($curlHandler);
        curl_close($curlHandler);
    }
}

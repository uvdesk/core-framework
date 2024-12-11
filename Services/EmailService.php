<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\EmailTemplates;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft\MicrosoftApp;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft\MicrosoftAccount;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Website;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\Microsoft\Graph as MicrosoftGraph;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Webkul\UVDesk\MailboxBundle\Services\MailboxService;
use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\AppTransportConfigurationInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Services\MicrosoftIntegration;

class EmailService
{
    private $request;
    private $container;
    private $entityManager;
    private $session;
    private $mailer;

    public function __construct(ContainerInterface $container, RequestStack $request, EntityManagerInterface $entityManager, SessionInterface $session, MailboxService $mailboxService, MicrosoftIntegration $microsoftIntegration)
    {
        $this->request = $request;
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->mailboxService = $mailboxService;
        $this->microsoftIntegration = $microsoftIntegration;
    }

    public function trans($text)
    {
        return $this->container->get('translator')->trans($text);
    }

    public function getEmailPlaceHolders($params)
    {
        $placeHolders = [];
        $allEmailPlaceholders = [];
        $template = is_array($params) ? ($params['match'] . 'Note') : (!empty($params) ? $params : 'template');

        if ($template == 'template') {
            $placeHolders = [
                'ticket' => [
                    'id' => [
                        'title' => $this->trans('Ticket Id'),
                        'info'  => $this->trans('ticket.id.placeHolders.info'),
                    ],
                    'subject' => [
                        'title' => $this->trans('Ticket Subject'),
                        'info'  => $this->trans('ticket.subject.placeHolders.info'),
                    ],
                    'message' => [
                        'title' => $this->trans('Ticket Message'),
                        'info'  => $this->trans('ticket.message.placeHolders.info'),
                    ],
                    'attachments' => [
                        'title' => $this->trans('Ticket Attachments'),
                        'info'  => $this->trans('ticket.attachments.placeHolders.info'),
                    ],
                    'threadMessage' => [
                        'title' => $this->trans('Ticket Thread Message'),
                        'info'  => $this->trans('ticket.threadMessage.placeHolders.info'),
                    ],
                    'tags' => [
                        'title' => $this->trans('Ticket Tags'),
                        'info'  => $this->trans('ticket.tags.placeHolders.info'),
                    ],
                    'source' => [
                        'title' => $this->trans('Ticket Source'),
                        'info'  => $this->trans('ticket.source.placeHolders.info'),
                    ],
                    'status' => [
                        'title' => $this->trans('Ticket Status'),
                        'info'  => $this->trans('ticket.status.placeHolders.info'),
                    ],
                    'priority' => [
                        'title' => $this->trans('Ticket Priority'),
                        'info'  => $this->trans('ticket.priority.placeHolders.info'),
                    ],
                    'group' => [
                        'title' => $this->trans('Ticket Group'),
                        'info'  => $this->trans('ticket.group.placeHolders.info'),
                    ],
                    'team' => [
                        'title' => $this->trans('Ticket Team'),
                        'info'  => $this->trans('ticket.team.placeHolders.info'),
                    ],
                    'customerName' => [
                        'title' => $this->trans('Ticket Customer Name'),
                        'info'  => $this->trans('ticket.customerName.placeHolders.info'),
                    ],
                    'customerEmail' => [
                        'title' => $this->trans('Ticket Customer Email'),
                        'info'  => $this->trans('ticket.customerEmail.placeHolders.info'),
                    ],
                    'agentName' => [
                        'title' => $this->trans('Ticket Agent Name'),
                        'info'  => $this->trans('ticket.agentName.placeHolders.info'),
                    ],
                    'agentEmail' => [
                        'title' => $this->trans('Ticket Agent Email'),
                        'info'  => $this->trans('ticket.agentEmail.placeHolders.info'),
                    ],
                    'agentLink' => [
                        'title' => $this->trans('Ticket Agent Link'),
                        'info'  => $this->trans('ticket.link.placeHolders.info'),
                    ],
                    'customerLink' => [
                        'title' => $this->trans('Ticket Customer Link'),
                        'info'  => $this->trans('ticket.link.placeHolders.info'),
                    ],
                    'collaboratorName' => [
                        'title' => $this->trans('Last Collaborator Name'),
                        'info'  => $this->trans('ticket.collaborator.name.placeHolders.info'),
                    ],
                    'collaboratorEmail' => [
                        'title' => $this->trans('Last Collaborator Email'),
                        'info'  => $this->trans('ticket.collaborator.email.placeHolders.info'),
                    ],
                ],
                'user'  => [
                    'userName' => [
                        'title' => $this->trans('Agent/ Customer Name'),
                        'info'  => $this->trans('user.name.info'),
                    ],
                    'userEmail' => [
                        'title' => $this->trans('Email'),
                        'info'  => $this->trans('user.email.info'),
                    ],
                    'accountValidationLink' => [
                        'title' => $this->trans('Account Validation Link'),
                        'info'  => $this->trans('user.account.validate.link.info'),
                    ],
                    'forgotPasswordLink' => [
                        'title' => $this->trans('Password Forgot Link'),
                        'info'  => $this->trans('user.password.forgot.link.info'),
                    ],
                ],
                'global' => [
                    'companyName' => [
                        'title' => $this->trans('Company Name'),
                        'info'  => $this->trans('global.companyName'),
                    ],
                    'companyLogo' => [
                        'title' => $this->trans('Company Logo'),
                        'info'  => $this->trans('global.companyLogo'),
                    ],
                    'companyUrl' => [
                        'title' => $this->trans('Company URL'),
                        'info'  => $this->trans('global.companyUrl'),
                    ],
                ],
            ];
        } elseif ($template == 'savedReply') {
            $placeHolders = [
                'ticket' => [
                    'id' => [
                        'title' => $this->trans('Ticket Id'),
                        'info'  => $this->trans('ticket.id.placeHolders.info'),
                    ],
                    'subject' => [
                        'title' => $this->trans('Ticket Subject'),
                        'info'  => $this->trans('ticket.subject.placeHolders.info'),
                    ],
                    'status' => [
                        'title' => $this->trans('Ticket Status'),
                        'info'  => $this->trans('ticket.status.placeHolders.info'),
                    ],
                    'priority' => [
                        'title' => $this->trans('Ticket Priority'),
                        'info'  => $this->trans('ticket.priority.placeHolders.info'),
                    ],
                    'group' => [
                        'title' => $this->trans('Ticket Group'),
                        'info'  => $this->trans('ticket.group.placeHolders.info'),
                    ],
                    'team' => [
                        'title' => $this->trans('Ticket Team'),
                        'info'  => $this->trans('ticket.team.placeHolders.info'),
                    ],
                    'customerName' => [
                        'title' => $this->trans('Ticket Customer Name'),
                        'info'  => $this->trans('ticket.customerName.placeHolders.info'),
                    ],
                    'customerEmail' => [
                        'title' => $this->trans('Ticket Customer Email'),
                        'info'  => $this->trans('ticket.customerEmail.placeHolders.info'),
                    ],
                    'agentName' => [
                        'title' => $this->trans('Ticket Agent Name'),
                        'info'  => $this->trans('ticket.agentName.placeHolders.info'),
                    ],
                    'agentEmail' => [
                        'title' => $this->trans('Ticket Agent Email'),
                        'info'  => $this->trans('ticket.agentEmail.placeHolders.info'),
                    ],
                    'link' => [
                        'title' => $this->trans('Ticket Link'),
                        'info'  => $this->trans('ticket.link.placeHolders.info'),
                    ],
                ],
            ];
        } elseif ($template == 'ticketNote') {
            $placeHolders = [
                'type' => [
                    'previousType' => [
                        'title' => $this->trans('Previous Type'),
                        'info'  => $this->trans('type.previous.placeHolders.info'),
                    ],
                    'updatedType' => [
                        'title' => $this->trans('Updated Type'),
                        'info'  => $this->trans('type.updated.placeHolders.info'),
                    ],
                ],
                'status' => [
                    'previousStatus' => [
                        'title' => $this->trans('Previous Status'),
                        'info'  => $this->trans('status.previous.placeHolders.info'),
                    ],
                    'updatedStatus' => [
                        'title' => $this->trans('Updated Status'),
                        'info'  => $this->trans('status.updated.placeHolders.info'),
                    ],
                ],
                'group' => [
                    'previousGroup' => [
                        'title' => $this->trans('Previous Group'),
                        'info'  => $this->trans('group.previous.placeHolders.info'),
                    ],
                    'updatedGroup' => [
                        'title' => $this->trans('Updated Group'),
                        'info'  => $this->trans('group.updated.placeHolders.info'),
                    ],
                ],
                'team' => [
                    'previousTeam' => [
                        'title' => $this->trans('Previous Team'),
                        'info'  => $this->trans('team.previous.placeHolders.info'),
                    ],
                    'updatedTeam' => [
                        'title' => $this->trans('Updated Team'),
                        'info'  => $this->trans('team.updated.placeHolders.info'),
                    ],
                ],
                'priority' => [
                    'previousPriority' => [
                        'title' => $this->trans('Previous Priority'),
                        'info'  => $this->trans('priority.previous.placeHolders.info'),
                    ],
                    'updatedPriority' => [
                        'title' => $this->trans('Updated Priority'),
                        'info'  => $this->trans('priority.updated.placeHolders.info'),
                    ],
                ],
                'agent' => [
                    'previousAgent' => [
                        'title' => $this->trans('Previous Agent'),
                        'info'  => $this->trans('agent.previous.placeHolders.info'),
                    ],
                    'updatedAgent' => [
                        'title' => $this->trans('Updated Agent'),
                        'info'  => $this->trans('agent.updated.placeHolders.info'),
                    ],
                    'responsePerformingAgent' => [
                        'title' => $this->trans('Response Performing Agent'),
                        'info'  => $this->trans('agent.response.placeHolders.info'),
                    ],
                ],
            ];
        } elseif($template == 'manualNote') {
            $placeHolders = [
                'ticket' => [
                    'id' => [
                        'title' => $this->trans('Ticket Id'),
                        'info'  => $this->trans('ticket.id.placeHolders.info'),
                    ],
                    'subject' => [
                        'title' => $this->trans('Ticket Subject'),
                        'info'  => $this->trans('ticket.subject.placeHolders.info'),
                    ],
                    'status' => [
                        'title' => $this->trans('Ticket Status'),
                        'info'  => $this->trans('ticket.status.placeHolders.info'),
                    ],
                    'priority' => [
                        'title' => $this->trans('Ticket Priority'),
                        'info'  => $this->trans('ticket.priority.placeHolders.info'),
                    ],
                    'group' => [
                        'title' => $this->trans('Ticket Group'),
                        'info'  => $this->trans('ticket.group.placeHolders.info'),
                    ],
                    'team' => [
                        'title' => $this->trans('Ticket Team'),
                        'info'  => $this->trans('ticket.team.placeHolders.info'),
                    ],
                    'customerName' => [
                        'title' => $this->trans('Ticket Customer Name'),
                        'info'  => $this->trans('ticket.customerName.placeHolders.info'),
                    ],
                    'customerEmail' => [
                        'title' => $this->trans('Ticket Customer Email'),
                        'info'  => $this->trans('ticket.customerEmail.placeHolders.info'),
                    ],
                    'agentName' => [
                        'title' => $this->trans('Ticket Agent Name'),
                        'info'  => $this->trans('ticket.agentName.placeHolders.info'),
                    ],
                    'agentEmail' => [
                        'title' => $this->trans('Ticket Agent Email'),
                        'info'  => $this->trans('ticket.agentEmail.placeHolders.info'),
                    ],
                ],
            ];
        }

        return $placeHolders;
    }

    public function getEmailPlaceholderValues(User $user, $userType = 'member')
    {
        if (null == $user->getVerificationCode()) {
            // Set user verification code
            $user->setVerificationCode(TokenGenerator::generateToken());

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $router = $this->container->get('router');
        $helpdeskWebsite = $this->entityManager->getRepository(Website::class)->findOneByCode('helpdesk');

        // Link to company knowledgebase
        if (false == array_key_exists('UVDeskSupportCenterBundle', $this->container->getParameter('kernel.bundles'))) {
            $companyURL = $this->container->getParameter('uvdesk.site_url');
        } else {
            $companyURL = $router->generate('helpdesk_knowledgebase', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        // Resolve path to helpdesk brand image
        $companyLogoURL = sprintf('http://%s%s', $this->container->getParameter('uvdesk.site_url'), '/bundles/uvdeskcoreframework/images/uv-avatar-uvdesk.png');
        $helpdeskKnowledgebaseWebsite = $this->entityManager->getRepository(Website::class)->findOneByCode('knowledgebase');

        if (!empty($helpdeskKnowledgebaseWebsite) && null != $helpdeskKnowledgebaseWebsite->getLogo()) {
            $companyLogoURL = sprintf('http://%s%s', $this->container->getParameter('uvdesk.site_url'), $helpdeskKnowledgebaseWebsite->getLogo());
        }
        
        // Link to update account login credentials
        $updateCredentialsURL = $router->generate( 'helpdesk_update_account_credentials', [
            'email' => $user->getEmail(),
            'verificationCode' => $user->getVerificationCode(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $placeholderParams = [
            'user.userName'              => $user->getFullName(),
            'user.userEmail'             => $user->getEmail(),
            'user.assignUserEmail'       => $user->getEmail(),
            'user.forgotPasswordLink'    => "<a href='$updateCredentialsURL'>$updateCredentialsURL</a>",
            'user.accountValidationLink' => "<a href='$updateCredentialsURL'>$updateCredentialsURL</a>",
            'global.companyName'         => $helpdeskWebsite->getName(),
            'global.companyLogo'         => "<img style='max-height:60px' src='$companyLogoURL'/>",
            'global.companyUrl'          => "<a href='$companyURL'>$companyURL</a>",
        ];
        
        return $placeholderParams;
    }

    public function getTicketPlaceholderValues(Ticket $ticket, $type = "")
    {
        $supportTeam = $ticket->getSupportTeam();
        $supportGroup = $ticket->getSupportGroup();
        $supportTags = array_map(function ($supportTag) { return $supportTag->getName(); }, $ticket->getSupportTags()->toArray());
        
        $router = $this->container->get('router');
        $helpdeskWebsite = $this->entityManager->getRepository(Website::class)->findOneByCode('helpdesk');
        
        // Resolve path to helpdesk brand image
        $companyLogoURL = sprintf('http://%s%s', $this->container->getParameter('uvdesk.site_url'), '/bundles/uvdeskcoreframework/images/uv-avatar-uvdesk.png');
        $helpdeskKnowledgebaseWebsite = $this->entityManager->getRepository(Website::class)->findOneByCode('knowledgebase');

        if (!empty($helpdeskKnowledgebaseWebsite) && null != $helpdeskKnowledgebaseWebsite->getLogo()) {
            $companyLogoURL = sprintf('http://%s%s', $this->container->getParameter('uvdesk.site_url'), $helpdeskKnowledgebaseWebsite->getLogo());
        }
        
        // Link to company knowledgebase
        if (false == array_key_exists('UVDeskSupportCenterBundle', $this->container->getParameter('kernel.bundles'))) {
            $companyURL = $this->container->getParameter('uvdesk.site_url');
        } else {
            $companyURL = $router->generate('helpdesk_knowledgebase', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $customerPartialDetails = $ticket->getCustomer()->getCustomerInstance()->getPartialDetails();
        $agentPartialDetails = $ticket->getAgent() ? $ticket->getAgent()->getAgentInstance()->getPartialDetails() : null;

        //Ticket Url and create ticket url for agent
        $viewTicketURLAgent = $router->generate('helpdesk_member_ticket', [
            'ticketId' => $ticket->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $generateTicketURLAgent = $router->generate('helpdesk_member_create_ticket', [], UrlGeneratorInterface::ABSOLUTE_URL);

        if (false != array_key_exists('UVDeskSupportCenterBundle', $this->container->getParameter('kernel.bundles'))) {
                $viewTicketURL = $router->generate('helpdesk_customer_ticket', [
                    'id' => $ticket->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
    
                $generateTicketURLCustomer = $router->generate('helpdesk_customer_create_ticket', [], UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            $viewTicketURL = '';
            $generateTicketURLCustomer = '';
        }

        $placeholderParams = [
            'ticket.id'                        => $ticket->getId(),
            'ticket.subject'                   => $ticket->getSubject(),
            'ticket.message'                   => (count($ticket->getThreads())) > 0 ? preg_replace("/<img[^>]+\>/i", "", $ticket->getThreads()->get(0)->getMessage()) : preg_replace("/<img[^>]+\>/i", "", $this->container->get('ticket.service')->getInitialThread($ticket->getId())->getMessage()),
            'ticket.threadMessage'             => $this->threadMessage($ticket),
            'ticket.tags'                      => implode(',', $supportTags),
            'ticket.source'                    => ucfirst($ticket->getSource()),
            'ticket.status'                    => $ticket->getStatus()->getDescription(),
            'ticket.priority'                  => $ticket->getPriority()->getDescription(),
            'ticket.team'                      => $supportTeam ? $supportTeam->getName() : '',
            'ticket.group'                     => $supportGroup ? $supportGroup->getName() : '',
            'ticket.customerName'              => $customerPartialDetails['name'],
            'ticket.customerEmail'             => $customerPartialDetails['email'],
            'ticket.agentName'                 => !empty($agentPartialDetails) ? $agentPartialDetails['name'] : '',
            'ticket.agentEmail'                => !empty($agentPartialDetails) ? $agentPartialDetails['email'] : '',
            'ticket.attachments'               => '',
            'ticket.collaboratorName'          => $this->getCollaboratorName($ticket),
            'ticket.collaboratorEmail'         => $this->getCollaboratorEmail($ticket),
            'ticket.agentLink'                 => sprintf("<a href='%s'>#%s</a>", $viewTicketURLAgent, $ticket->getId()),
            'ticket.ticketGenerateUrlAgent'    => sprintf("<a href='%s'>click here</a>", $generateTicketURLAgent),
            'ticket.customerLink'              => sprintf("<a href='%s'>#%s</a>", $viewTicketURL, $ticket->getId()),
            'ticket.ticketGenerateUrlCustomer' => sprintf("<a href='%s'>click here</a>", $generateTicketURLCustomer),
            'global.companyName'               => $helpdeskWebsite->getName(),
            'global.companyLogo'               => "<img style='max-height:60px' src='$companyLogoURL'/>",
            'global.companyUrl'                => "<a href='$companyURL'>$companyURL</a>",
        ];

        return $placeholderParams;
    }

    public function threadMessage($ticket)
    {
        $message = null;
        if (isset($ticket->createdThread) && $ticket->createdThread->getThreadType() != "note") {
            return preg_replace("/<img[^>]+\>/i", "", $ticket->createdThread->getMessage());
        } elseif (isset($ticket->currentThread) && $ticket->currentThread->getThreadType() != "note") {
            return  preg_replace("/<img[^>]+\>/i", "", $ticket->currentThread->getMessage());
        } else {
            $messages = $ticket->getThreads();
            for ($i = count($messages) - 1 ; $i >= 0  ; $i--) { 
                if (isset($messages[$i]) && $messages[$i]->getThreadType() != "note") {
                    return preg_replace("/<img[^>]+\>/i", "", $messages[$i]->getMessage());
                }
            }
        }

        return "";
    }


    public function processEmailSubject($subject, array $emailPlaceholders = [])
    {
        foreach ($emailPlaceholders as $var => $value) {
            $subject = strtr($subject, ["{%$var%}" => $value, "{% $var %}" => $value]);
        }
        
        return $subject;
    }

    public function processEmailContent($content, array $emailPlaceholders = [], $isSavedReply = false)
    {
        $twigTemplatingEngine = $this->container->get('twig');
        $baseEmailTemplate = $this->container->getParameter('uvdesk.default.templates.email');

        foreach ($emailPlaceholders as $var => $value) {
            $content = strtr($content, ["{%$var%}" => $value, "{% $var %}" => $value]);
        }

        $content = $isSavedReply ? stripslashes($content) : htmlspecialchars_decode(preg_replace(['#&lt;script&gt;#', '#&lt;/script&gt;#'], ['&amp;lt;script&amp;gt;', '&amp;lt;/script&amp;gt;'], $content));
        
        return $twigTemplatingEngine->render($baseEmailTemplate, ['message' => $content]);
    }

    public function sendMail($subject, $content, $recipient, array $headers = [], $mailboxEmail = null, array $attachments = [], $cc = [], $bcc = [])
    {
        $mailer_type = $this->container->getParameter('uvdesk.support_email.mailer_type');

        if (empty($mailboxEmail)) {
            // Send email on behalf of support helpdesk
            $supportEmail = $this->container->getParameter('uvdesk.support_email.id');
            $supportEmailName = $this->container->getParameter('uvdesk.support_email.name');
            $mailerID = $this->container->getParameter('uvdesk.support_email.mailer_id');
        } else {
            // Register automations conditionally if AutomationBundle has been added as an dependency.
            if (!array_key_exists('UVDeskMailboxBundle', $this->container->getParameter('kernel.bundles'))) {
                return;
            } else {
                // Send email on behalf of configured mailbox
                try {
                    $mailbox = $this->container->get('uvdesk.mailbox')->getMailboxByEmail($mailboxEmail);

                    if (true === $mailbox['enabled']) {
                        if ($mailer_type == 'swiftmailer_id') {
                            $supportEmail = $mailbox['email'];
                            $supportEmailName = $mailbox['name'];
                            $mailerID = $mailbox['smtp_swift_mailer_server']['mailer_id'];
                        } else {
                            $supportEmail = $mailbox['email'];
                            $supportEmailName = $mailbox['name'];
                            $mailerID = $this->container->getParameter('uvdesk.support_email.mailer_id');
                        }
                    } else {
                        return;
                    }
                } catch (\Exception $e) {
                    // @TODO: Log exception - Mailbox not found
                    return;
                }
            }
        }

        // Send emails only if any mailer configuration is available
        $mailer = null;
        $mailboxConfigurations = $this->mailboxService->parseMailboxConfigurations();
        
        if ($mailer_type != 'swiftmailer_id') {
            $mailbox = $mailboxConfigurations->getMailboxById($mailerID);

            if (!$mailbox) {
                return;
            }

            $mailboxSmtpConfiguration = $mailbox?->getSmtpConfiguration();
        }

        if ($mailer_type == 'swiftmailer_id') {
            // Retrieve mailer to be used for sending emails
            try {
                $mailer = $this->container->get('swiftmailer.mailer' . (('default' == $mailerID) ? '' : ".$mailerID"));
                $mailer->getTransport()->setPassword(base64_decode($mailer->getTransport()->getPassword()));
            } catch (\Exception $e) {
                // @TODO: Log exception - Mailer not found
                return;
            }

            // Format email address collections
            $ccAddresses = [];

            foreach ($cc as $_emailAddress) {
                if (strpos($_emailAddress, "<") !== false && strpos($_emailAddress, ">") !== false) {
                    $_recipientName = trim(substr($_emailAddress, 0, strpos($_emailAddress, "<")));
                    $_recipientAddress = substr($_emailAddress, strpos($_emailAddress, "<") + 1);
                    $_recipientAddress = substr($_recipientAddress, 0, strpos($_recipientAddress, ">"));

                    $ccAddresses[$_recipientAddress] = $_recipientName;
                } else {
                    $ccAddresses[] = $_emailAddress;
                }
            }

            $bccAddresses = [];

            foreach ($bcc as $_emailAddress) {
                if (strpos($_emailAddress, "<") !== false && strpos($_emailAddress, ">") !== false) {
                    $_recipientName = trim(substr($_emailAddress, 0, strpos($_emailAddress, "<")));
                    $_recipientAddress = substr($_emailAddress, strpos($_emailAddress, "<") + 1);
                    $_recipientAddress = substr($_recipientAddress, 0, strpos($_recipientAddress, ">"));

                    $bccAddresses[$_recipientAddress] = $_recipientName;
                } else {
                    $bccAddresses[] = $_emailAddress;
                }
            }

            // Create a message
            $message = (new \Swift_Message($subject))
                ->setFrom([$supportEmail => $supportEmailName])
                ->setTo($recipient)
                ->setBcc($bccAddresses)
                ->setCc($ccAddresses)
                ->setBody($content, 'text/html')
                ->addPart(strip_tags($content), 'text/plain')
            ;

            foreach ($attachments as $attachment) {
                if (!empty($attachment['path']) && !empty($attachment['name'])) {
                    $message->attach(\Swift_Attachment::fromPath($attachment['path'])->setFilename($attachment['name']));

                    continue;
                }

                $message->attach(\Swift_Attachment::fromPath($attachment));
            }

            $messageHeaders = $message->getHeaders();

            foreach ($headers as $headerName => $headerValue) {
                if (is_array($headerValue) && !empty($headerValue['messageId'])) {
                    $headerValue = $headerValue['messageId'];
                }

                if (is_array($headerValue) || empty($headerValue)) {
                    continue; // Skip arrays that don't have a 'messageId'.
                }
            
                $messageHeaders->addTextHeader($headerName, $headerValue);
            }

            try {
                $messageId = $message->getId();
                $mailer->send($message);

                return "<$messageId>";
            } catch (\Exception $e) {
                // @TODO: Log exception
                $this->session->getFlashBag()->add('warning', $this->trans('Warning! Swiftmailer not working. An error has occurred while sending emails!'));
            }

            return null;
        } else {
            // Prepare email
            $email = new Email();
            $email
                ->from(new Address($supportEmail, $supportEmailName))
                ->subject($subject)
                ->text(strip_tags($content))
                ->html($content)
            ;

            // Manage email recipients
            if (!empty($recipient)) {
                $email->to($recipient);
            }

            foreach ($cc as $emailAddress) {
                $email->addCc($emailAddress);
            }

            foreach ($bcc as $emailAddress) {
                $email->addBcc($emailAddress);
            }

            // Manage email attachments
            foreach ($attachments as $attachment) {
                if (!empty($attachment['path']) && !empty($attachment['name'])) {
                    $email->attachFromPath($attachment['path'], $attachment['name']);
                    
                    continue;
                } 

                $email->attachFromPath($attachment);
            }

            // Configure email headers
            $emailHeaders = $email->getHeaders();

            foreach ($headers as $name => $value) {
                if (is_array($value) && ! empty($value['messageId'])) {
                    $value = $value['messageId'];
                }
            
                if (is_array($value) || empty($value)) {
                    continue; // Skip arrays that don't have a 'messageId'.
                }
            
                $emailHeaders->addTextHeader($name, $value);
            }

            // Send email
            $messageId = null;

            try {
                if ($mailboxSmtpConfiguration instanceof AppTransportConfigurationInterface) {
                    $microsoftApp = $this->entityManager->getRepository(MicrosoftApp::class)->findOneByClientId($mailboxSmtpConfiguration->getClient());

                    if (empty($microsoftApp)) {
                        $this->session->getFlashBag()->add('warning', $this->trans('An unexpected error occurred while trying to send email. Please try again later.'));
                        $this->session->getFlashBag()->add('warning', $this->trans('No associated microsoft apps were found for configured mailbox.'));
    
                        return null;
                    }

                    $microsoftAccount = $this->entityManager->getRepository(MicrosoftAccount::class)->findOneBy([
                        'email'        => $mailboxSmtpConfiguration->getUsername(), 
                        'microsoftApp' => $microsoftApp, 
                    ]);
    
                    if (empty($microsoftAccount)) {
                        $this->session->getFlashBag()->add('warning', $this->trans('An unexpected error occurred while trying to send email. Please try again later.'));
                        $this->session->getFlashBag()->add('warning', $this->trans('No associated microsoft account was found for configured mailbox.'));
    
                        return null;
                    }

                    $credentials = json_decode($microsoftAccount->getCredentials(), true);
                    $emailParams = [
                        'subject' => $subject, 
                        'body' => [
                            'contentType' => 'HTML', 
                            'content'     => $content, 
                        ], 
                        'toRecipients' => [
                            [
                                'emailAddress' => [
                                    'address' => $recipient, 
                                ], 
                            ], 
                        ], 
                    ];

                    foreach ($headers as $name => $value) {
                        if ($name == 'X-Transport' || empty($value)) {
                            continue;
                        }

                        if (is_array($value)) {
                            $value = $value['messageId'];
                        }

                        $emailParams['internetMessageHeaders'][] = [
                            'name' => "x-$name", 
                            'value' => $value, 
                        ];
                    }

                    $graphResponse = MicrosoftGraph\Me::sendMail($credentials['access_token'], $emailParams);

                    // Refresh access token if expired
                    if (!empty($graphResponse['error'])) {
                        if (!empty($graphResponse['error']['code']) && $graphResponse['error']['code'] == 'InvalidAuthenticationToken') {
                            $tokenResponse = $this->microsoftIntegration->refreshAccessToken($microsoftApp, $credentials['refresh_token']);

                            if (!empty($tokenResponse['access_token'])) {
                                $microsoftAccount
                                    ->setCredentials(json_encode($tokenResponse))
                                ;
                                
                                $this->entityManager->persist($microsoftAccount);
                                $this->entityManager->flush();
                                
                                $credentials = json_decode($microsoftAccount->getCredentials(), true);

                                $graphResponse = MicrosoftGraph\Me::sendMail($credentials['access_token'], $emailParams);
                            }
                        }
                    }
                } else {
                    $dsn = strtr("smtp://{email}:{password}@{host}:{port}", [
                        "{email}"    => $mailboxSmtpConfiguration->getUsername(), 
                        "{password}" => $mailboxSmtpConfiguration->getPassword(), 
                        "{host}"     => $mailboxSmtpConfiguration->getHost(), 
                        "{port}"     => $mailboxSmtpConfiguration->getPort(), 
                    ]);

                    if (false == $mailbox->getIsStrictModeEnabled()) {
                        $dsn .= "?verify_peer=0";
                    }

                    $transport = Transport::fromDsn($dsn);

                    $sentMessage = $transport->send($email);

                    if (!empty($sentMessage)) {
                        $messageId = $sentMessage->getMessageId();
                    }
                }
            } catch (\Exception $e) {
                // @TODO: Log exception
                $this->session->getFlashBag()->add('warning', $this->trans('An unexpected error occurred while trying to send email. Please try again later.'));
                $this->session->getFlashBag()->add('warning', $this->trans($e->getMessage()));

                return null;
            }
    
            return !empty($messageId) ? "<$messageId>" : null;
        }
    }

    public function getCollaboratorName($ticket)
    {
        $name = null;
        $ticket->lastCollaborator = null;

        if ($ticket->getCollaborators() != null && count($ticket->getCollaborators()) > 0) {
            try {
                $ticket->lastCollaborator = $ticket->getCollaborators()[ -1 + count($ticket->getCollaborators()) ];
            } catch(\Exception $e) {
            }
        }

        if ($ticket->lastCollaborator != null) {
            $name =  $ticket->lastCollaborator->getFirstName()." ".$ticket->lastCollaborator->getLastName();
        }
        
        return $name != null ? $name : '';
    }

    public function getCollaboratorEmail($ticket)
    {
        $email = null;
        $ticket->lastCollaborator = null;

        if ($ticket->getCollaborators() != null && count($ticket->getCollaborators()) > 0) {
            try {
                $ticket->lastCollaborator = $ticket->getCollaborators()[ -1 + count($ticket->getCollaborators()) ];
            } catch(\Exception $e) {
            }
        }

        if ($ticket->lastCollaborator != null) {
            $email = $ticket->lastCollaborator->getEmail();
        }
        
        return $email != null ? $email : '';;
    }
}

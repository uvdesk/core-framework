<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Services;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webkul\UVDesk\SupportCenterBundle\Entity\Article;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Website;
use Webkul\UVDesk\MailboxBundle\Services\MailboxService;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\AutomationBundle\Entity\PreparedResponses;
use UVDesk\CommunityPackages\UVDesk as UVDeskCommunityPackages;
use Webkul\UVDesk\CoreFrameworkBundle\Services\FileUploadService;
use Webkul\UVDesk\SupportCenterBundle\Entity\KnowledgebaseWebsite;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreFrameworkEntity;
use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;

class TicketService
{
    const PATH_TO_CONFIG = '/config/packages/uvdesk_mailbox.yaml';

    protected $container;
    protected $requestStack;
    protected $entityManager;
    protected $fileUploadService;
    protected $userService;

    public function __construct(
        ContainerInterface $container,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        FileUploadService $fileUploadService,
        UserService $userService,
        MailboxService $mailboxService,
        TranslatorInterface $translator
    ) {
        $this->container = $container;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->fileUploadService = $fileUploadService;
        $this->userService = $userService;
        $this->mailboxService = $mailboxService;
        $this->translator = $translator;
    }

    public function getAllMailboxes()
    {
        $mailboxConfiguration = $this->mailboxService->parseMailboxConfigurations();

        $collection = array_map(function ($mailbox) {
            return [
                'id'        => $mailbox->getId(),
                'name'      => $mailbox->getName(),
                'isEnabled' => $mailbox->getIsEnabled(),
                'email'     => $mailbox->getImapConfiguration()->getUsername(),
            ];
        }, array_values($mailboxConfiguration->getMailboxes()));

        return ($collection ?? []);
    }

    public function generateRandomEmailReferenceId()
    {
        $emailDomain = null;
        $mailbox = $this->mailboxService->parseMailboxConfigurations()->getDefaultMailbox();

        if (!empty($mailbox)) {
            $smtpConfiguration = $mailbox->getSmtpConfiguration();

            if (!empty($smtpConfiguration)) {
                $emailDomain = substr($smtpConfiguration->getUsername(), strpos($smtpConfiguration->getUsername(), '@'));
            }
        }

        if (!empty($emailDomain)) {
            return sprintf("<%s%s>", TokenGenerator::generateToken(20, '0123456789abcdefghijklmnopqrstuvwxyz'), $emailDomain);
        }

        return null;
    }

    // @TODO: Refactor this out of this service. Use UserService::getSessionUser() instead.
    public function getUser()
    {
        return $this->container->get('user.service')->getCurrentUser();
    }

    public function getDefaultType()
    {
        $typeCode = $this->container->getParameter('uvdesk.default.ticket.type');
        $ticketType = $this->entityManager->getRepository(CoreFrameworkEntity\TicketType::class)->findOneByCode($typeCode);

        return !empty($ticketType) ? $ticketType : null;
    }

    public function getDefaultStatus()
    {
        $statusCode = $this->container->getParameter('uvdesk.default.ticket.status');
        $ticketStatus = $this->entityManager->getRepository(CoreFrameworkEntity\TicketStatus::class)->findOneByCode($statusCode);

        return !empty($ticketStatus) ? $ticketStatus : null;
    }

    public function getUserPresenceStatus()
    {
        $presenceStatus = $this->entityManager->getRepository(CoreFrameworkEntity\Website::class)->findOneById(1);

        return !empty($presenceStatus) ? $presenceStatus->getDisplayUserPresenceIndicator() : null;
    }

    public function getDefaultPriority()
    {
        $priorityCode = $this->container->getParameter('uvdesk.default.ticket.priority');
        $ticketPriority = $this->entityManager->getRepository(CoreFrameworkEntity\TicketPriority::class)->findOneByCode($priorityCode);

        return !empty($ticketPriority) ? $ticketPriority : null;
    }

    public function appendTwigSnippet($snippet = '')
    {
        switch ($snippet) {
            case 'createMemberTicket':
                return $this->getMemberCreateTicketSnippet();
                break;
            default:
                break;
        }

        return '';
    }

    public function getMemberCreateTicketSnippet()
    {
        $twigTemplatingEngine = $this->container->get('twig');
        $ticketTypeCollection = $this->entityManager->getRepository(CoreFrameworkEntity\TicketType::class)->findByIsActive(true);

        try {
            if ($this->userService->isFileExists('apps/uvdesk/custom-fields')) {
                $headerCustomFields = $this->container->get('uvdesk_package_custom_fields.service')->getCustomFieldsArray('user');
            } else if ($this->userService->isFileExists('apps/uvdesk/form-component')) {
                $headerCustomFields = $this->container->get('uvdesk_package_form_component.service')->getCustomFieldsArray('user');
            }
        } catch (\Exception $e) {
            // @TODO: Log exception message
        }

        return $twigTemplatingEngine->render('@UVDeskCoreFramework/Snippets/createMemberTicket.html.twig', [
            'ticketTypeCollection' => $ticketTypeCollection,
            'headerCustomFields'   => $headerCustomFields ?? null,
        ]);
    }

    public function getCustomerCreateTicketCustomFieldSnippet()
    {
        try {
            if ($this->userService->isFileExists('apps/uvdesk/custom-fields')) {
                $customFields = $this->container->get('uvdesk_package_custom_fields.service')->getCustomFieldsArray('customer');
            } else if ($this->userService->isFileExists('apps/uvdesk/form-component')) {
                $customFields = $this->container->get('uvdesk_package_form_component.service')->getCustomFieldsArray('customer');
            }
        } catch (\Exception $e) {
            // @TODO: Log exception message
        }

        return $customFields ?? null;
    }

    public function createTicket(array $params = [])
    {
        $thread = $this->entityManager->getRepository(CoreFrameworkEntity\Thread::class)->findOneByMessageId($params['messageId']);

        if (empty($thread)) {
            $user = $this->entityManager->getRepository(CoreFrameworkEntity\User::class)->findOneByEmail($params['from']);

            if (empty($user) || null == $user->getCustomerInstance()) {
                $role = $this->entityManager->getRepository(CoreFrameworkEntity\SupportRole::class)->findOneByCode($params['role']);

                if (empty($role)) {
                    throw new \Exception("The requested role '" . $params['role'] . "' does not exist.");
                }

                // Create CoreFrameworkEntity\User Instance
                $user = $this->container->get('user.service')->createUserInstance($params['from'], $params['name'], $role, [
                    'source' => strtolower($params['source']),
                    'active' => true,
                ]);
            }

            $params['role'] = 4;
            $params['mailboxEmail'] = current($params['replyTo']);
            $params['customer'] = $params['user'] = $user;

            return $this->createTicketBase($params);
        }

        return;
    }

    public function getDemanedFilterOptions($filterType, $ids)
    {
        $qb = $this->entityManager->createQueryBuilder();
        switch ($filterType) {
            case 'agent':
                $qb->select("u.id,u.email,CONCAT(u.firstName,' ', u.lastName) AS name")->from(CoreFrameworkEntity\User::class, 'u')
                    ->leftJoin(CoreFrameworkEntity\UserInstance::class, 'ud', 'WITH', 'u.id = ud.user')
                    ->where('ud.supportRole != :roles')
                    ->andwhere('ud.isActive = 1')
                    ->andwhere('u.id IN (:ids)')
                    ->setParameter('roles', 4)
                    ->setParameter('ids', $ids)
                    ->orderBy('name', 'ASC');

                return $qb->getQuery()->getArrayResult();
            case 'customer':
                $qb->select("c.id,c.email,CONCAT(c.firstName,' ', c.lastName) AS name")->from(CoreFrameworkEntity\User::class, 'c')
                    ->leftJoin(CoreFrameworkEntity\UserInstance::class, 'ud', 'WITH', 'c.id = ud.user')
                    ->where('ud.supportRole = :roles')
                    ->andwhere('ud.isActive = 1')
                    ->andwhere('c.id IN (:ids)')
                    ->setParameter('roles', 4)
                    ->setParameter('ids', $ids)
                    ->orderBy('name', 'ASC');

                return $qb->getQuery()->getArrayResult();
            case 'group':
                $qb->select("ug.id,ug.description")->from(CoreFrameworkEntity\SupportGroup::class, 'ug')
                    ->andwhere('ug.isEnabled = 1')
                    ->andwhere('ug.id IN (:ids)')
                    ->setParameter('ids', $ids)
                    ->orderBy('ug.description', 'ASC');

                return $qb->getQuery()->getArrayResult();
            case 'team':
                $qb->select("usg.id,usg.description")->from(CoreFrameworkEntity\SupportTeam::class, 'usg')
                    ->andwhere('usg.isActive = 1')
                    ->andwhere('usg.id IN (:ids)')
                    ->setParameter('ids', $ids)
                    ->orderBy('usg.description', 'ASC');

                return $qb->getQuery()->getArrayResult();
            case 'tag':
                $qb->select("t.id,t.name")->from(CoreFrameworkEntity\Tag::class, 't')
                    ->andwhere('t.id IN (:ids)')
                    ->setParameter('ids', $ids)
                    ->orderBy('t.name', 'ASC');

                return $qb->getQuery()->getArrayResult();
        }
    }

    public function createTicketBase(array $ticketData = [])
    {
        if ('email' == $ticketData['source']) {
            try {
                if (array_key_exists('UVDeskMailboxBundle', $this->container->getParameter('kernel.bundles'))) {
                    $mailbox = $this->mailboxService->getMailboxByEmail($ticketData['mailboxEmail']);
                    $ticketData['mailboxEmail'] = $mailbox['email'];
                }
            } catch (\Exception $e) {
                // No mailbox found for this email. Skip ticket creation.
                return;
            }
        }

        // Set Defaults
        $ticketType = !empty($ticketData['type']) ? $ticketData['type'] : $this->getDefaultType();
        $ticketStatus = !empty($ticketData['status']) ? $ticketData['status'] : $this->getDefaultStatus();
        $ticketPriority = !empty($ticketData['priority']) ? $ticketData['priority'] : $this->getDefaultPriority();

        if ('email' == $ticketData['source']) {
            $ticketMessageId = !empty($ticketData['messageId']) ? $ticketData['messageId'] : null;
        } else {
            $ticketMessageId = $this->generateRandomEmailReferenceId();
        }

        $ticketData['type'] = $ticketType;
        $ticketData['status'] = $ticketStatus;
        $ticketData['priority'] = $ticketPriority;
        $ticketData['messageId'] = $ticketMessageId;
        $ticketData['isTrashed'] = false;

        $ticket = new CoreFrameworkEntity\Ticket();
        foreach ($ticketData as $property => $value) {
            $callable = 'set' . ucwords($property);

            if (method_exists($ticket, $callable)) {
                $ticket->$callable($value);
            }
        }

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        return $this->createThread($ticket, $ticketData);
    }

    public function createThread(CoreFrameworkEntity\Ticket $ticket, array $threadData)
    {
        $threadData['isLocked'] = 0;

        if ('forward' === $threadData['threadType']) {
            $threadData['replyTo'] = $threadData['to'];
        }

        $collaboratorEmails = [];
        // check if $threadData['cc'] is not empty then merge it with $collaboratorEmails
        if (! empty($threadData['cc'])) {
            if (! is_array($threadData['cc'])) {
                $threadData['cc'] = [$threadData['cc']];
            }

            $collaboratorEmails = array_merge($collaboratorEmails, $threadData['cc']);
        }

        // check if $threadData['cccol'] is not empty
        if (! empty($threadData['cccol'])) {
            if (! is_array($threadData['cccol'])) {
                $threadData['cccol'] = [$threadData['cccol']];
            }

            $collaboratorEmails = array_merge($collaboratorEmails, $threadData['cccol']);
        }

        if (! empty($collaboratorEmails)) {
            $threadData['cc'] = $collaboratorEmails;
        }

        $thread = new CoreFrameworkEntity\Thread();
        $thread->setTicket($ticket);
        $thread->setCreatedAt(new \DateTime());
        $thread->setUpdatedAt(new \DateTime());

        if ($threadData['message']) {
            $threadData['message'] = htmlspecialchars($this->sanitizeMessage($threadData['message']), ENT_QUOTES, 'UTF-8');
        }

        if ($threadData['threadType'] != "note") {
            foreach ($threadData as $property => $value) {
                if (!empty($value)) {
                    $callable = 'set' . ucwords($property);
                    if (method_exists($thread, $callable)) {
                        $thread->$callable($value);
                    }
                }
            }
        } else {
            $this->setTicketNotePlaceholderValue($thread, $threadData, $ticket);
        }

        // Update ticket reference ids is thread message id is defined
        if (null != $thread->getMessageId() && false === strpos($ticket->getReferenceIds(), $thread->getMessageId())) {
            $updatedReferenceIds = $ticket->getReferenceIds() . ' ' . $thread->getMessageId();
            $ticket->setReferenceIds($updatedReferenceIds);

            $this->entityManager->persist($ticket);
        }

        if ('reply' === $threadData['threadType']) {
            if ('agent' === $threadData['createdBy']) {
                // Ticket has been updated by support agents, mark as agent replied | customer view pending
                $ticket->setIsCustomerViewed(false);
                $ticket->setIsReplied(true);

                $customerName = $ticket->getCustomer()->getFirstName() . ' ' . $ticket->getCustomer()->getLastName();

                $agentActivity = new CoreFrameworkEntity\AgentActivity();
                $agentActivity->setThreadType('reply');
                $agentActivity->setTicket($ticket);
                $agentActivity->setAgent($thread->getUser());
                $agentActivity->setCustomerName($customerName);
                $agentActivity->setAgentName('agent');
                $agentActivity->setCreatedAt(new \DateTime());

                $this->entityManager->persist($agentActivity);
            } else {
                // Ticket has been updated by customer, mark as agent view | reply pending
                $ticket->setIsAgentViewed(false);
                $ticket->setIsReplied(false);
            }

            $this->entityManager->persist($ticket);
        } else if ('create' === $threadData['threadType']) {
            $ticket->setIsReplied(false);
            $this->entityManager->persist($ticket);

            $customerName = $ticket->getCustomer()->getFirstName() . ' ' . $ticket->getCustomer()->getLastName();

            $agentActivity = new CoreFrameworkEntity\AgentActivity();
            $agentActivity->setThreadType('create');
            $agentActivity->setTicket($ticket);
            $agentActivity->setAgent($thread->getUser());
            $agentActivity->setCustomerName($customerName);
            $agentActivity->setAgentName('agent');
            $agentActivity->setCreatedAt(new \DateTime());

            $this->entityManager->persist($agentActivity);
        }

        $ticket->currentThread = $this->entityManager->getRepository(CoreFrameworkEntity\Thread::class)->getTicketCurrentThread($ticket);

        $this->entityManager->persist($thread);
        $this->entityManager->flush();

        $ticket->createdThread = $thread;

        // Uploading Attachments.
        if (
            (isset($threadData['attachments']) && ! empty($threadData['attachments'])) || (isset($threadData['attachmentContent']) && ! empty($threadData['attachmentContent']))
        ) {
            if ('email' == $threadData['source']) {
                // Saving Email attachments in case of outlook with $threadData['attachmentContent']
                try {
                    $attachments = ! empty($threadData['attachments']) ? $threadData['attachments'] : $threadData['attachmentContent'];

                    if (! empty($attachments)) {
                        $this->saveThreadEmailAttachments($thread, $threadData['attachments'], $threadData['attachmentContent'] ?? []);
                    }
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }
            } else if (!empty($threadData['attachments'])) {
                try {
                    $this->fileUploadService->validateAttachments($threadData['attachments']);
                    $this->saveThreadAttachment($thread, $threadData['attachments']);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }
            }
        }

        // Send Webhook Notification
        $this->sendWebhookNotificationAction($thread);

        return $thread;
    }

    public function setTicketNotePlaceholderValue($thread, $threadData, $ticket)
    {
        if (!empty($threadData)) {
            foreach ($threadData as $property => $value) {
                if (!empty($value)) {
                    $callable = 'set' . ucwords($property);
                    if (method_exists($thread, $callable)) {
                        if ($callable != "setMessage") {
                            $thread->$callable($value);
                        } else {
                            $notesPlaceholders = $this->getNotePlaceholderValues($ticket, 'customer');
                            $content = $value;
                            foreach ($notesPlaceholders as $key => $val) {
                                if (strpos($value, "{%$key%}") !== false) {
                                    $content = strtr($value, ["{%$key%}" => $val, "{% $key %}" => $val]);
                                }
                            }

                            $content = stripslashes($content);
                            $thread->$callable($content);
                        }
                    }
                }
            }
        }
    }

    public function saveThreadAttachment($thread, array $attachments)
    {
        $prefix = 'threads/' . $thread->getId();
        $uploadManager = $this->container->get('uvdesk.core.file_system.service')->getUploadManager();

        foreach ($attachments as $attachment) {
            $uploadedFileAttributes = $uploadManager->uploadFile($attachment, $prefix);

            if (!empty($uploadedFileAttributes['path'])) {
                ($threadAttachment = new CoreFrameworkEntity\Attachment())
                    ->setThread($thread)
                    ->setName($uploadedFileAttributes['name'])
                    ->setPath($uploadedFileAttributes['path'])
                    ->setSize($uploadedFileAttributes['size'])
                    ->setContentType($uploadedFileAttributes['content-type']);

                $this->entityManager->persist($threadAttachment);
            }
        }

        $this->entityManager->flush();
    }

    public function saveThreadEmailAttachments($thread, array $attachments, array $attachmentContents)
    {
        $prefix = 'threads/' . $thread->getId();
        $uploadManager = $this->container->get('uvdesk.core.file_system.service')->getUploadManager();

        // Upload thread attachments
        foreach ($attachments as $attachment) {
            $uploadedFileAttributes = $uploadManager->uploadEmailAttachment($attachment, $prefix);

            if (!empty($uploadedFileAttributes['path'])) {
                ($threadAttachment = new CoreFrameworkEntity\Attachment())
                    ->setThread($thread)
                    ->setName($uploadedFileAttributes['name'])
                    ->setPath($uploadedFileAttributes['path'])
                    ->setSize($uploadedFileAttributes['size'])
                    ->setContentType($uploadedFileAttributes['content-type']);

                $this->entityManager->persist($threadAttachment);
            }
        }

        // Microsoft 365 Attachments.
        $basePublicPath = realpath(__DIR__ . '/../../../../public') . '/';
        $prefixOutlook = $basePublicPath . 'assets/threads/' . $thread->getId() . '/';

        foreach ($attachmentContents as $attachmentContent) {
            $decodedData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $attachmentContent['content']));

            $filePath = $prefixOutlook . $attachmentContent['name'];

            if (!is_dir($prefixOutlook) && !is_dir($prefixOutlook)) {
                mkdir($prefixOutlook, 0755, true);
            }

            $relativePath = str_replace($basePublicPath, '', $filePath);

            try {
                $result = file_put_contents($filePath, $decodedData);
                if ($result === false) {
                    throw new \Exception("Failed to write file to $filePath");
                }
            } catch (\Exception $e) {
            }

            if (!empty($filePath)) {
                $threadAttachment = (new CoreFrameworkEntity\Attachment())
                    ->setThread($thread)
                    ->setName($attachmentContent['name'])
                    ->setPath($relativePath)
                    ->setSize(strlen($decodedData)) // Use actual file size
                    ->setContentType($attachmentContent['mimeType']);

                $this->entityManager->persist($threadAttachment);
            }
        }

        $this->entityManager->flush();
    }

    public function getTypes()
    {
        static $types;
        if (null !== $types)
            return $types;

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('tp.id', 'tp.code As name')->from(CoreFrameworkEntity\TicketType::class, 'tp')
            ->andWhere('tp.isActive = 1')
            ->orderBy('tp.code', 'ASC');

        return $types = $qb->getQuery()->getArrayResult();
    }

    public function getStatus()
    {
        static $statuses;
        if (null !== $statuses)
            return $statuses;

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('ts')->from(CoreFrameworkEntity\TicketStatus::class, 'ts');
        // $qb->orderBy('ts.sortOrder', Criteria::ASC);

        return $statuses = $qb->getQuery()->getArrayResult();
    }

    public function getTicketTotalThreads($ticketId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(th.id) as threadCount')->from(CoreFrameworkEntity\Ticket::class, 't')
            ->leftJoin('t.threads', 'th')
            ->andWhere('t.id = :ticketId')
            ->andWhere('th.threadType = :threadType')
            ->setParameter('threadType', 'reply')
            ->setParameter('ticketId', $ticketId);

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id) as threadCount')->from(CoreFrameworkEntity\Thread::class, 't')
            ->andWhere('t.ticket = :ticketId')
            ->andWhere('t.threadType = :threadType')
            ->setParameter('threadType', 'reply')
            ->setParameter('ticketId', $ticketId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getTicketTags($request = null)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('tg')->from(CoreFrameworkEntity\Tag::class, 'tg');

        if ($request) {
            $qb->andWhere("tg.name LIKE :tagName");
            $qb->setParameter('tagName', '%' . urldecode(trim($request->query->get('query'))) . '%');
            $qb->andWhere("tg.id NOT IN (:ids)");
            $qb->setParameter('ids', explode(',', urldecode($request->query->get('not'))));
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function paginateMembersTicketCollection(Request $request)
    {
        $params = $request->query->all();
        $activeUser = $this->container->get('user.service')->getSessionUser();
        $activeUserTimeZone = $this->entityManager->getRepository(CoreFrameworkEntity\Website::class)->findOneBy(['code' => 'Knowledgebase']);
        $agentTimeZone = !empty($activeUser->getTimezone()) ? $activeUser->getTimezone() : $activeUserTimeZone->getTimezone();
        $agentTimeFormat = !empty($activeUser->getTimeformat()) ? $activeUser->getTimeformat() : $activeUserTimeZone->getTimeformat();

        $ticketRepository = $this->entityManager->getRepository(CoreFrameworkEntity\Ticket::class);

        $website = $this->entityManager->getRepository(CoreFrameworkEntity\Website::class)->findOneBy(['code' => 'helpdesk']);
        $timeZone = $website->getTimezone();
        $timeFormat = $website->getTimeformat();

        $supportGroupReference = $this->entityManager->getRepository(CoreFrameworkEntity\User::class)->getUserSupportGroupReferences($activeUser);
        $supportTeamReference  = $this->entityManager->getRepository(CoreFrameworkEntity\User::class)->getUserSupportTeamReferences($activeUser);

        // Get base query
        $baseQuery = $ticketRepository->prepareBaseTicketQuery($activeUser, $supportGroupReference, $supportTeamReference, $params);
        $ticketTabs = $ticketRepository->getTicketTabDetails($activeUser, $supportGroupReference, $supportTeamReference, $params);

        // Apply Pagination
        $pageNumber = !empty($params['page']) ? (int) $params['page'] : 1;
        $itemsLimit = !empty($params['limit']) ? (int) $params['limit'] : $ticketRepository::DEFAULT_PAGINATION_LIMIT;

        if (isset($params['repliesLess']) || isset($params['repliesMore'])) {
            $paginationOptions = ['wrap-queries' => true];
            $paginationQuery = $baseQuery->getQuery()
                ->setHydrationMode(Query::HYDRATE_ARRAY);
        } else {
            $paginationOptions = ['distinct' => true];
            $paginationQuery = $baseQuery->getQuery()
                ->setHydrationMode(Query::HYDRATE_ARRAY)
                ->setHint('knp_paginator.count', isset($params['status']) ? $ticketTabs[$params['status']] : $ticketTabs[1]);
        }

        $pagination = $this->container->get('knp_paginator')->paginate($paginationQuery, $pageNumber, $itemsLimit, $paginationOptions);
        // Process Pagination Response
        $ticketCollection = [];
        $paginationParams = $pagination->getParams();
        $paginationData = $pagination->getPaginationData();

        $paginationParams['page'] = 'replacePage';
        $paginationData['url'] = '#' . $this->container->get('uvdesk.service')->buildPaginationQuery($paginationParams);
        // $container->get('default.service')->buildSessionUrl('ticket',$queryParameters);


        $ticketThreadCountQueryTemplate = $this->entityManager->createQueryBuilder()
            ->select('COUNT(thread.id) as threadCount')
            ->from(CoreFrameworkEntity\Ticket::class, 'ticket')
            ->leftJoin('ticket.threads', 'thread')
            ->where('ticket.id = :ticketId')
            ->andWhere('thread.threadType = :threadType')->setParameter('threadType', 'reply');

        foreach ($pagination->getItems() as $ticketDetails) {
            $ticket = array_shift($ticketDetails);

            $ticketThreadCountQuery = clone $ticketThreadCountQueryTemplate;
            $ticketThreadCountQuery->setParameter('ticketId', $ticket['id']);

            $totalTicketReplies = (int) $ticketThreadCountQuery->getQuery()->getSingleScalarResult();
            $ticketHasAttachments = false;
            $dbTime = $ticket['createdAt'];

            $formattedTime = $this->fomatTimeByPreference($dbTime, $timeZone, $timeFormat, $agentTimeZone, $agentTimeFormat);

            $currentDateTime  = new \DateTime('now');
            $lastReply = $this->getLastReply($ticket['id']);

            if ($lastReply) {
                $lastRepliedTime =
                    $this->time2string($currentDateTime->getTimeStamp() - $lastReply['createdAt']->getTimeStamp());
            } else {
                $lastRepliedTime =
                    $this->time2string($currentDateTime->getTimeStamp() - $ticket['createdAt']->getTimeStamp());
            }

            $ticketResponse = [
                'id'                => $ticket['id'],
                'subject'           => $ticket['subject'],
                'isStarred'         => $ticket['isStarred'],
                'isAgentView'       => $ticket['isAgentViewed'],
                'isTrashed'         => $ticket['isTrashed'],
                'source'            => $ticket['source'],
                'group'             => $ticketDetails['groupName'],
                'team'              => $ticketDetails['teamName'],
                'priority'          => $ticket['priority'],
                'type'              => $ticketDetails['typeName'],
                'timestamp'         => $formattedTime['dateTimeZone'],
                'formatedCreatedAt' => $formattedTime['dateTimeZone']->format($formattedTime['timeFormatString']),
                'totalThreads'      => $totalTicketReplies,
                'agent'             => null,
                'customer'          => null,
                'hasAttachments'    => $ticketHasAttachments,
                'lastReplyTime'     => $lastRepliedTime
            ];

            if (!empty($ticketDetails['agentId'])) {
                $ticketResponse['agent'] = [
                    'id' => $ticketDetails['agentId'],
                    'name' => $ticketDetails['agentName'],
                    'smallThumbnail' => $ticketDetails['smallThumbnail'],
                ];
            }

            if (!empty($ticketDetails['customerId'])) {
                $ticketResponse['customer'] = [
                    'id'             => $ticketDetails['customerId'],
                    'name'           => $ticketDetails['customerName'],
                    'email'          => $ticketDetails['customerEmail'],
                    'smallThumbnail' => $ticketDetails['customersmallThumbnail'],
                ];
            }

            array_push($ticketCollection, $ticketResponse);
        }

        return [
            'tickets'    => $ticketCollection,
            'pagination' => $paginationData,
            'tabs'       => $ticketTabs,
            'labels' => [
                'predefind' => $this->getPredefindLabelDetails($activeUser, $supportGroupReference, $supportTeamReference, $params),
                'custom'    => $this->getCustomLabelDetails($this->container),
            ],

        ];
    }

    // Convert Timestamp to day/hour/min
    public function time2string($time)
    {
        $d = floor($time / 86400);
        $_d = ($d < 10 ? '0' : '') . $d;

        $h = floor(($time - $d * 86400) / 3600);
        $_h = ($h < 10 ? '0' : '') . $h;

        $m = floor(($time - ($d * 86400 + $h * 3600)) / 60);
        $_m = ($m < 10 ? '0' : '') . $m;

        $s = $time - ($d * 86400 + $h * 3600 + $m * 60);
        $_s = ($s < 10 ? '0' : '') . $s;

        $time_str = "0 minutes";
        if ($_d != 00)
            $time_str = $_d . " " . 'days';
        elseif ($_h != 00)
            $time_str = $_h . " " . 'hours';
        elseif ($_m != 00)
            $time_str = $_m . " " . 'minutes';

        return $time_str . " " . "ago";
    }

    public function getPredefindLabelDetails(CoreFrameworkEntity\User $currentUser, array $supportGroupIds = [], array $supportTeamIds = [], array $params = [])
    {
        $data = array();
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $ticketRepository = $this->entityManager->getRepository(CoreFrameworkEntity\Ticket::class);
        $queryBuilder->select('COUNT(DISTINCT ticket.id) as ticketCount')->from(CoreFrameworkEntity\Ticket::class, 'ticket');

        // // applyFilter according to permission
        $queryBuilder->where('ticket.isTrashed != 1');
        $userInstance = $currentUser->getAgentInstance();

        if (
            !empty($userInstance) &&  'ROLE_AGENT' == $userInstance->getSupportRole()->getCode()
            && $userInstance->getTicketAccesslevel() != 1
        ) {
            $supportGroupIds = implode(',', $supportGroupIds);
            $supportTeamIds = implode(',', $supportTeamIds);

            if ($userInstance->getTicketAccesslevel() == 4) {
                $queryBuilder->andWhere('ticket.agent = ' . $currentUser->getId());
            } elseif ($userInstance->getTicketAccesslevel() == 2) {
                $query = '';
                if ($supportGroupIds) {
                    $query .= ' OR supportGroup.id IN(' . $supportGroupIds . ') ';
                }
                if ($supportTeamIds) {
                    $query .= ' OR supportTeam.id IN(' . $supportTeamIds . ') ';
                }
                $queryBuilder->leftJoin('ticket.supportGroup', 'supportGroup')
                    ->leftJoin('ticket.supportTeam', 'supportTeam')
                    ->andWhere('( ticket.agent = ' . $currentUser->getId() . $query . ')');
            } elseif ($userInstance->getTicketAccesslevel() == 3) {
                $query = '';
                if ($supportTeamIds) {
                    $query .= ' OR supportTeam.id IN(' . $supportTeamIds . ') ';
                }
                $queryBuilder->leftJoin('ticket.supportGroup', 'supportGroup')
                    ->leftJoin('ticket.supportTeam', 'supportTeam')
                    ->andWhere('( ticket.agent = ' . $currentUser->getId() . $query . ')');
            }
        }

        // for all tickets count
        $data['all'] = $queryBuilder->getQuery()->getSingleScalarResult();

        // for new tickets count
        $newQb = clone $queryBuilder;
        $newQb->andWhere('ticket.isNew = 1');
        $data['new'] = $newQb->getQuery()->getSingleScalarResult();

        // for unassigned tickets count
        $unassignedQb = clone $queryBuilder;
        $unassignedQb->andWhere("ticket.agent is NULL");
        $data['unassigned'] = $unassignedQb->getQuery()->getSingleScalarResult();

        // for unanswered ticket count
        $unansweredQb = clone $queryBuilder;
        $unansweredQb->andWhere('ticket.isReplied = 0');
        $data['notreplied'] = $unansweredQb->getQuery()->getSingleScalarResult();

        // for my tickets count
        $mineQb = clone $queryBuilder;
        $mineQb->andWhere("ticket.agent = :agentId")
            ->setParameter('agentId', $currentUser->getId());
        $data['mine'] = $mineQb->getQuery()->getSingleScalarResult();

        // for starred tickets count
        $starredQb = clone $queryBuilder;
        $starredQb->andWhere('ticket.isStarred = 1');
        $data['starred'] = $starredQb->getQuery()->getSingleScalarResult();

        // for trashed tickets count
        $trashedQb = clone $queryBuilder;
        $trashedQb->where('ticket.isTrashed = 1');
        if ($currentUser->getRoles()[0] != 'ROLE_SUPER_ADMIN' && $userInstance->getTicketAccesslevel() != 1) {
            $trashedQb->andWhere('ticket.agent = ' . $currentUser->getId());
        }
        $data['trashed'] = $trashedQb->getQuery()->getSingleScalarResult();

        return $data;
    }

    public function paginateMembersTicketThreadCollection(CoreFrameworkEntity\Ticket $ticket, Request $request)
    {
        $params = $request->query->all();
        $entityManager = $this->entityManager;
        $activeUser = $this->container->get('user.service')->getSessionUser();

        $activeUserTimeZone = $this->entityManager->getRepository(CoreFrameworkEntity\Website::class)->findOneBy(['code' => 'Knowledgebase']);
        $agentTimeZone = !empty($activeUser->getTimezone()) ? $activeUser->getTimezone() : $activeUserTimeZone->getTimezone();
        $agentTimeFormat = !empty($activeUser->getTimeformat()) ? $activeUser->getTimeformat() : $activeUserTimeZone->getTimeformat();

        $threadRepository = $entityManager->getRepository(CoreFrameworkEntity\Thread::class);
        $uvdeskFileSystemService = $this->container->get('uvdesk.core.file_system.service');

        // Get base query
        $enableLockedThreads = $this->container->get('user.service')->isAccessAuthorized('ROLE_AGENT_MANAGE_LOCK_AND_UNLOCK_THREAD');
        $baseQuery = $threadRepository->prepareBasePaginationRecentThreadsQuery($ticket, $params, $enableLockedThreads);

        // Apply Pagination
        $paginationItemsQuery = clone $baseQuery;
        $totalPaginationItems = $paginationItemsQuery->select('COUNT(DISTINCT thread.id)')->getQuery()->getSingleScalarResult();

        $pageNumber = !empty($params['page']) ? (int) $params['page'] : 1;
        $itemsLimit = !empty($params['limit']) ? (int) $params['limit'] : $threadRepository::DEFAULT_PAGINATION_LIMIT;

        $paginationOptions = ['distinct' => true];
        $paginationQuery = $baseQuery->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY)->setHint('knp_paginator.count', (int) $totalPaginationItems);

        $pagination = $this->container->get('knp_paginator')->paginate($paginationQuery, $pageNumber, $itemsLimit, $paginationOptions);

        // Process Pagination Response
        $threadCollection = [];
        $paginationParams = $pagination->getParams();
        $paginationData = $pagination->getPaginationData();

        $website = $this->entityManager->getRepository(CoreFrameworkEntity\Website::class)->findOneBy(['code' => 'helpdesk']);
        $timeZone = $website->getTimezone();
        $timeFormat = $website->getTimeformat();

        if (!empty($params['threadRequestedId'])) {
            $requestedThreadCollection = $baseQuery
                ->andWhere('thread.id >= :threadRequestedId')->setParameter('threadRequestedId', (int) $params['threadRequestedId'])
                ->getQuery()->getArrayResult();

            $totalRequestedThreads = count($requestedThreadCollection);
            $paginationData['current'] = ceil($totalRequestedThreads / $threadRepository::DEFAULT_PAGINATION_LIMIT);

            if ($paginationData['current'] > 1) {
                $paginationData['firstItemNumber'] = 1;
                $paginationData['lastItemNumber'] = $totalRequestedThreads;
                $paginationData['next'] = ceil(($totalRequestedThreads + 1) / $threadRepository::DEFAULT_PAGINATION_LIMIT);
            }
        }

        $paginationParams['page'] = 'replacePage';
        $paginationData['url'] = '#' . $this->container->get('uvdesk.service')->buildPaginationQuery($paginationParams);
        foreach ($pagination->getItems() as $threadDetails) {
            $dbTime = $threadDetails['createdAt'];
            $formattedTime = $this->fomatTimeByPreference($dbTime, $timeZone, $timeFormat, $agentTimeZone, $agentTimeFormat);

            $threadResponse = [
                'id'                => $threadDetails['id'],
                'user'              => null,
                'fullname'          => null,
                'reply'             => html_entity_decode($threadDetails['message']),
                'source'            => $threadDetails['source'],
                'threadType'        => $threadDetails['threadType'],
                'userType'          => $threadDetails['createdBy'],
                'timestamp'         => $formattedTime['dateTimeZone'],
                'formatedCreatedAt' => $formattedTime['dateTimeZone']->format($formattedTime['timeFormatString']),
                'bookmark'          => $threadDetails['isBookmarked'],
                'isLocked'          => $threadDetails['isLocked'],
                'replyTo'           => $threadDetails['replyTo'],
                'cc'                => $threadDetails['cc'],
                'bcc'               => $threadDetails['bcc'],
                'attachments'       => $threadDetails['attachments'],
            ];

            if (! empty($threadDetails['user'])) {

                if (!empty(trim($threadDetails['user']['firstName']))) {
                    $threadResponse['fullname'] = trim($threadDetails['user']['firstName'] . ' ' . $threadDetails['user']['lastName']);
                }

                $threadResponse['user'] = [
                    'id' => $threadDetails['user']['id'],
                    'smallThumbnail' => $threadDetails['user']['userInstance'][0]['profileImagePath'],
                    'name' => $threadResponse['fullname'],
                ];
            } else {
                $threadResponse['fullname'] = 'System';
            }

            if (!empty($threadResponse['attachments'])) {
                $threadResponse['attachments'] = array_map(function ($attachment) use ($entityManager, $uvdeskFileSystemService) {
                    $attachmentReferenceObject = $entityManager->getReference(CoreFrameworkEntity\Attachment::class, $attachment['id']);
                    return $uvdeskFileSystemService->getFileTypeAssociations($attachmentReferenceObject);
                }, $threadResponse['attachments']);
            }

            array_push($threadCollection, $threadResponse);
        }

        return [
            'threads'    => $threadCollection,
            'pagination' => $paginationData,
        ];
    }

    public function massXhrUpdate(Request $request)
    {
        $params = $request->request->get('data');

        foreach ($params['ids'] as $ticketId) {
            $ticket = $this->entityManager->getRepository(CoreFrameworkEntity\Ticket::class)->find($ticketId);

            if (false == $this->isTicketAccessGranted($ticket)) {
                throw new \Exception('Access Denied', 403);
            }

            if (empty($ticket)) {
                continue;
            }

            switch ($params['actionType']) {
                case 'trashed':
                    if (false == $ticket->getIsTrashed()) {
                        $ticket->setIsTrashed(true);

                        $this->entityManager->persist($ticket);
                    }

                    // Trigger ticket delete event
                    $event = new CoreWorkflowEvents\Ticket\Delete();
                    $event
                        ->setTicket($ticket);

                    $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');

                    break;
                case 'delete':
                    $threads = $ticket->getThreads();
                    $fileService = new Filesystem();

                    if (count($threads) > 0) {
                        foreach ($threads as $thread) {
                            if (!empty($thread)) {
                                $fileService->remove($this->container->getParameter('kernel.project_dir') . '/public/assets/threads/' . $thread->getId());
                            }
                        }
                    }

                    $this->entityManager->remove($ticket);

                    break;
                case 'restored':
                    if (true == $ticket->getIsTrashed()) {
                        $ticket->setIsTrashed(false);

                        $this->entityManager->persist($ticket);
                    }

                    break;
                case 'agent':
                    if ($ticket->getAgent() == null || $ticket->getAgent() && $ticket->getAgent()->getId() != $params['targetId']) {

                        $agent = $this->entityManager->getRepository(CoreFrameworkEntity\User::class)->find($params['targetId']);
                        $ticket->setAgent($agent);

                        $this->entityManager->persist($ticket);

                        // Trigger Agent Assign event
                        $event = new CoreWorkflowEvents\Ticket\Agent();
                        $event
                            ->setTicket($ticket);

                        $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');
                    }

                    break;
                case 'status':
                    if ($ticket->getStatus() == null || $ticket->getStatus() && $ticket->getStatus()->getId() != $params['targetId']) {

                        $status = $this->entityManager->getRepository(CoreFrameworkEntity\TicketStatus::class)->findOneById($params['targetId']);
                        $ticket->setStatus($status);

                        $this->entityManager->persist($ticket);

                        // Trigger ticket status event
                        $event = new CoreWorkflowEvents\Ticket\Status();
                        $event
                            ->setTicket($ticket);

                        $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');
                    }

                    break;
                case 'type':
                    if ($ticket->getType() == null || $ticket->getType() && $ticket->getType()->getId() != $params['targetId']) {

                        $type = $this->entityManager->getRepository(CoreFrameworkEntity\TicketType::class)->findOneById($params['targetId']);
                        $ticket->setType($type);

                        $this->entityManager->persist($ticket);

                        // Trigger ticket type event
                        $event = new CoreWorkflowEvents\Ticket\Type();
                        $event
                            ->setTicket($ticket);

                        $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');
                    }

                    break;
                case 'group':
                    if ($ticket->getSupportGroup() == null || $ticket->getSupportGroup() && $ticket->getSupportGroup()->getId() != $params['targetId']) {

                        $group = $this->entityManager->getRepository(CoreFrameworkEntity\SupportGroup::class)->find($params['targetId']);
                        $ticket->setSupportGroup($group);

                        $this->entityManager->persist($ticket);

                        // Trigger Support group event
                        $event = new CoreWorkflowEvents\Ticket\Group();
                        $event
                            ->setTicket($ticket);

                        $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');
                    }

                    break;
                case 'team':
                    if ($ticket->getSupportTeam() == null || $ticket->getSupportTeam() && $ticket->getSupportTeam()->getId() != $params['targetId']) {

                        $team = $this->entityManager->getRepository(CoreFrameworkEntity\SupportTeam::class)->find($params['targetId']);
                        $ticket->setSupportTeam($team);

                        $this->entityManager->persist($ticket);

                        // Trigger team event
                        $event = new CoreWorkflowEvents\Ticket\Team();
                        $event
                            ->setTicket($ticket);

                        $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');
                    }

                    break;
                case 'priority':
                    if ($ticket->getPriority() == null || $ticket->getPriority() && $ticket->getPriority()->getId() != $params['targetId']) {

                        $priority = $this->entityManager->getRepository(CoreFrameworkEntity\TicketPriority::class)->find($params['targetId']);
                        $ticket->setPriority($priority);

                        $this->entityManager->persist($ticket);

                        // Trigger ticket Priority event
                        $event = new CoreWorkflowEvents\Ticket\Priority();
                        $event
                            ->setTicket($ticket);

                        $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');
                    }

                    break;
                case 'label':
                    $label = $this->entityManager->getRepository(CoreFrameworkEntity\SupportLabel::class)->find($params['targetId']);

                    if ($label && !$this->entityManager->getRepository(CoreFrameworkEntity\Ticket::class)->isLabelAlreadyAdded($ticket, $label)) {
                        $ticket->addSupportLabel($label);
                    }

                    $this->entityManager->persist($ticket);

                    break;
                default:
                    break;
            }
        }

        $this->entityManager->flush();

        if ($params['actionType'] == 'trashed') {
            $message = 'Success ! Tickets moved to trashed successfully.';
        } elseif ($params['actionType'] == 'restored') {
            $message = 'Success ! Tickets restored successfully.';
        } elseif ($params['actionType'] == 'delete') {
            $message = 'Success ! Tickets removed successfully.';
        } elseif ($params['actionType'] == 'agent') {
            $message = 'Success ! Agent assigned successfully.';
        } elseif ($params['actionType'] == 'status') {
            $message = 'Success ! Tickets status updated successfully.';
        } elseif ($params['actionType'] == 'type') {
            $message = 'Success ! Tickets type updated successfully.';
        } elseif ($params['actionType'] == 'group') {
            $message = 'Success ! Tickets group updated successfully.';
        } elseif ($params['actionType'] == 'team') {
            $message = 'Success ! Tickets team updated successfully.';
        } elseif ($params['actionType'] == 'priority') {
            $message = 'Success ! Tickets priority updated successfully.';
        } elseif ($params['actionType'] == 'label') {
            $message = 'Success ! Tickets added to label successfully.';
        } else {
            $message = 'Success ! Tickets have been updated successfully';
        }

        return [
            'alertClass' => 'success',
            'alertMessage' => $this->trans($message),
        ];
    }

    public function getNotePlaceholderValues($ticket, $type = "customer")
    {
        $variables = array();
        $variables['ticket.id'] = $ticket->getId();
        $variables['ticket.subject'] = $ticket->getSubject();

        $variables['ticket.status'] = $ticket->getStatus()->getCode();
        $variables['ticket.priority'] = $ticket->getPriority()->getCode();
        if ($ticket->getSupportGroup())
            $variables['ticket.group'] = $ticket->getSupportGroup()->getName();
        else
            $variables['ticket.group'] = '';

        $variables['ticket.team'] = ($ticket->getSupportTeam() ? $ticket->getSupportTeam()->getName() : '');

        $customer = $this->container->get('user.service')->getCustomerPartialDetailById($ticket->getCustomer()->getId());
        $variables['ticket.customerName'] = $customer['name'];
        $userService = $this->container->get('user.service');

        $variables['ticket.agentName'] = '';
        $variables['ticket.agentEmail'] = '';
        if ($ticket->getAgent()) {
            $agent = $this->container->get('user.service')->getAgentDetailById($ticket->getAgent()->getId());
            if ($agent) {
                $variables['ticket.agentName'] = $agent['name'];
                $variables['ticket.agentEmail'] = $agent['email'];
            }
        }

        $router = $this->container->get('router');

        if ($type == 'customer') {
            $ticketListURL = $router->generate('helpdesk_member_ticket_collection', [
                'id' => $ticket->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            $ticketListURL = $router->generate('helpdesk_customer_ticket_collection', [
                'id' => $ticket->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $variables['ticket.link'] = sprintf("<a href='%s'>#%s</a>", $ticketListURL, $ticket->getId());

        return $variables;
    }

    public function paginateMembersTicketTypeCollection(Request $request)
    {
        // Get base query
        $params = $request->query->all();
        $ticketRepository = $this->entityManager->getRepository(CoreFrameworkEntity\Ticket::class);
        $paginationQuery = $ticketRepository->prepareBasePaginationTicketTypesQuery($params);

        // Apply Pagination
        $paginationOptions = ['distinct' => true];
        $pageNumber = !empty($params['page']) ? (int) $params['page'] : 1;
        $itemsLimit = !empty($params['limit']) ? (int) $params['limit'] : $ticketRepository::DEFAULT_PAGINATION_LIMIT;

        $pagination = $this->container->get('knp_paginator')->paginate($paginationQuery, $pageNumber, $itemsLimit, $paginationOptions);

        // Process Pagination Response
        $paginationParams = $pagination->getParams();
        $paginationData = $pagination->getPaginationData();

        $paginationParams['page'] = 'replacePage';
        $paginationData['url'] = '#' . $this->container->get('uvdesk.service')->buildPaginationQuery($paginationParams);

        return [
            'types' => array_map(function ($ticketType) {
                return [
                    'id'          => $ticketType->getId(),
                    'code'        => strtoupper($ticketType->getCode()),
                    'description' => $ticketType->getDescription(),
                    'isActive'    => $ticketType->getIsActive(),
                ];
            }, $pagination->getItems()),
            'pagination_data' => $paginationData,
        ];
    }

    public function paginateMembersTagCollection(Request $request)
    {
        // Get base query
        $params = $request->query->all();
        $ticketRepository = $this->entityManager->getRepository(CoreFrameworkEntity\Ticket::class);
        $baseQuery = $ticketRepository->prepareBasePaginationTagsQuery($params);

        // Apply Pagination
        $paginationResultsQuery = clone $baseQuery;
        $paginationResultsQuery->select('COUNT(supportTag.id)');
        $paginationQuery = $baseQuery->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY)->setHint('knp_paginator.count', count($paginationResultsQuery->getQuery()->getResult()));

        $paginationOptions = ['distinct' => true];
        $pageNumber = !empty($params['page']) ? (int) $params['page'] : 1;
        $itemsLimit = !empty($params['limit']) ? (int) $params['limit'] : $ticketRepository::DEFAULT_PAGINATION_LIMIT;

        $pagination = $this->container->get('knp_paginator')->paginate($paginationQuery, $pageNumber, $itemsLimit, $paginationOptions);

        // Process Pagination Response
        $paginationParams = $pagination->getParams();
        $paginationData = $pagination->getPaginationData();

        $paginationParams['page'] = 'replacePage';
        $paginationData['url'] = '#' . $this->container->get('uvdesk.service')->buildPaginationQuery($paginationParams);

        if (in_array('UVDeskSupportCenterBundle', array_keys($this->container->getParameter('kernel.bundles')))) {
            $articleRepository = $this->entityManager->getRepository(Article::class);

            return [
                'tags' => array_map(function ($supportTag) use ($articleRepository) {
                    return [
                        'id'           => $supportTag['id'],
                        'name'         => $supportTag['name'],
                        'ticketCount'  => $supportTag['totalTickets'],
                        'articleCount' => $articleRepository->getTotalArticlesBySupportTag($supportTag['id']),
                    ];
                }, $pagination->getItems()),
                'pagination_data' => $paginationData,
            ];
        } else {
            return [
                'tags' => array_map(function ($supportTag) {
                    return [
                        'id'          => $supportTag['id'],
                        'name'        => $supportTag['name'],
                        'ticketCount' => $supportTag['totalTickets'],
                    ];
                }, $pagination->getItems()),
                'pagination_data' => $paginationData,
            ];
        }
    }

    public function getTicketInitialThreadDetails(CoreFrameworkEntity\Ticket $ticket)
    {
        $initialThread = $this->entityManager->getRepository(CoreFrameworkEntity\Thread::class)->findOneBy([
            'ticket'     => $ticket,
            'threadType' => 'create',
        ]);

        if (!empty($initialThread)) {
            $author = $initialThread->getUser();
            $authorInstance = 'agent' == $initialThread->getCreatedBy() ? $author->getAgentInstance() : $author->getCustomerInstance();

            $threadDetails = [
                'id'          => $initialThread->getId(),
                'source'      => $initialThread->getSource(),
                'messageId'   => $initialThread->getMessageId(),
                'threadType'  => $initialThread->getThreadType(),
                'createdBy'   => $initialThread->getCreatedBy(),
                'message'     => html_entity_decode($initialThread->getMessage()),
                'attachments' => $initialThread->getAttachments(),
                'timestamp'   => $initialThread->getCreatedAt()->getTimestamp(),
                'createdAt'   => $initialThread->getCreatedAt()->format('d-m-Y h:ia'),
                'user'        => $authorInstance->getPartialDetails(),
                'cc'          => is_array($initialThread->getCc()) ? implode(', ', $initialThread->getCc()) : '',
            ];

            $attachments = $threadDetails['attachments']->getValues();

            if (!empty($attachments)) {
                $uvdeskFileSystemService = $this->container->get('uvdesk.core.file_system.service');

                $threadDetails['attachments'] = array_map(function ($attachment) use ($uvdeskFileSystemService) {
                    return $uvdeskFileSystemService->getFileTypeAssociations($attachment);
                }, $attachments);
            }
        }

        return $threadDetails ?? null;
    }

    public function getCreateReply($ticketId, $firewall = 'member')
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("th,a,u.id as userId")->from(CoreFrameworkEntity\Thread::class, 'th')
            ->leftJoin('th.ticket', 't')
            ->leftJoin('th.attachments', 'a')
            ->leftJoin('th.user', 'u')
            ->andWhere('t.id = :ticketId')
            ->andWhere('th.threadType = :threadType')
            ->setParameter('threadType', 'create')
            ->setParameter('ticketId', $ticketId)
            ->orderBy('th.id', 'DESC')
            ->getMaxResults(1);

        $threadResponse = $qb->getQuery()->getArrayResult();

        if ((!empty($threadResponse[0][0]))) {
            $threadDetails = $threadResponse[0][0];
            $userService = $this->container->get('user.service');

            if ($threadDetails['createdBy'] == 'agent') {
                $threadDetails['user'] = $userService->getAgentDetailById($threadResponse[0]['userId']);
            } else {
                $threadDetails['user'] = $userService->getCustomerPartialDetailById($threadResponse[0]['userId']);
            }

            $threadDetails['reply'] = html_entity_decode($threadDetails['message']);
            $threadDetails['formatedCreatedAt'] = $this->timeZoneConverter($threadDetails['createdAt']);
            $threadDetails['timestamp'] = $userService->convertToDatetimeTimezoneTimestamp($threadDetails['createdAt']);

            if (!empty($threadDetails['attachments'])) {
                $entityManager = $this->entityManager;
                $uvdeskFileSystemService = $this->container->get('uvdesk.core.file_system.service');

                $threadDetails['attachments'] = array_map(function ($attachment) use ($entityManager, $uvdeskFileSystemService, $firewall) {
                    $attachmentReferenceObject = $entityManager->getReference(CoreFrameworkEntity\Attachment::class, $attachment['id']);
                    return $uvdeskFileSystemService->getFileTypeAssociations($attachmentReferenceObject, $firewall);
                }, $threadDetails['attachments']);
            }
        }

        return $threadDetails ?? null;
    }

    public function hasAttachments($ticketId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("DISTINCT COUNT(a.id) as attachmentCount")->from(CoreFrameworkEntity\Thread::class, 'th')
            ->leftJoin('th.ticket', 't')
            ->leftJoin('th.attachments', 'a')
            ->andWhere('t.id = :ticketId')
            ->setParameter('ticketId', $ticketId);

        return intval($qb->getQuery()->getSingleScalarResult());
    }

    public function getAgentDraftReply()
    {
        $signature = $this->getUser()->getAgentInstance()->getSignature();

        return str_replace("\n", '<br/>', $signature);
    }

    public function trans($text)
    {
        return $this->container->get('translator')->trans($text);
    }

    public function getAllSources()
    {
        $sources = ['email' => 'Email', 'website' => 'Website'];

        return $sources;
    }

    public function getCustomLabelDetails($container)
    {
        $currentUser = $container->get('user.service')->getCurrentUser();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(DISTINCT t) as ticketCount,sl.id')->from(CoreFrameworkEntity\Ticket::class, 't')
            ->leftJoin('t.supportLabels', 'sl')
            ->andWhere('sl.user = :userId')
            ->setParameter('userId', $currentUser->getId())
            ->groupBy('sl.id');

        $ticketCountResult = $qb->getQuery()->getResult();

        $data = array();
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('sl.id,sl.name,sl.colorCode')->from(CoreFrameworkEntity\SupportLabel::class, 'sl')
            ->andWhere('sl.user = :userId')
            ->setParameter('userId', $currentUser->getId());

        $labels = $qb->getQuery()->getResult();

        foreach ($labels as $key => $label) {
            $labels[$key]['count'] = 0;
            foreach ($ticketCountResult as $ticketCount) {
                if (($label['id'] == $ticketCount['id']))
                    $labels[$key]['count'] = $ticketCount['ticketCount'] ?: 0;
            }
        }

        return $labels;
    }

    public function getLabels($request = null)
    {
        static $labels;
        if (null !== $labels)
            return $labels;

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('sl')->from(CoreFrameworkEntity\SupportLabel::class, 'sl')
            ->andWhere('sl.user = :userId')
            ->setParameter('userId', $this->getUser()->getId());

        if ($request) {
            $qb->andWhere("sl.name LIKE :labelName");
            $qb->setParameter('labelName', '%' . urldecode(trim($request->query->get('query'))) . '%');
        }

        return $labels = $qb->getQuery()->getArrayResult();
    }

    public function getTicketCollaborators($ticketId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("DISTINCT c.id, c.email, CONCAT(c.firstName,' ', c.lastName) AS name, userInstance.profileImagePath, userInstance.profileImagePath as smallThumbnail")->from(CoreFrameworkEntity\Ticket::class, 't')
            ->leftJoin('t.collaborators', 'c')
            ->leftJoin('c.userInstance', 'userInstance')
            ->andWhere('t.id = :ticketId')
            ->andWhere('userInstance.supportRole = :roles')
            ->setParameter('ticketId', $ticketId)
            ->setParameter('roles', 4)
            ->orderBy('name', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }

    public function getTicketTagsById($ticketId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('tg')->from(CoreFrameworkEntity\Tag::class, 'tg')
            ->leftJoin('tg.tickets', 't')
            ->andWhere('t.id = :ticketId')
            ->setParameter('ticketId', $ticketId);

        return $qb->getQuery()->getArrayResult();
    }

    public function getTicketLabels($ticketId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DISTINCT sl.id,sl.name,sl.colorCode')->from(CoreFrameworkEntity\Ticket::class, 't')
            ->leftJoin('t.supportLabels', 'sl')
            ->leftJoin('sl.user', 'slu')
            ->andWhere('slu.id = :userId')
            ->andWhere('t.id = :ticketId')
            ->setParameter('userId', $this->getUser()->getId())
            ->setParameter('ticketId', $ticketId);

        $result = $qb->getQuery()->getResult();

        return $result ? $result : [];
    }

    public function getUserLabels()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('sl')->from(CoreFrameworkEntity\SupportLabel::class, 'sl')
            ->leftJoin('sl.user', 'slu')
            ->andWhere('slu.id = :userId')
            ->setParameter('userId', $this->getUser()->getId());

        $result = $qb->getQuery()->getResult();

        return $result ? $result : [];
    }

    public function getTicketLabelsAll($ticketId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DISTINCT sl.id,sl.name,sl.colorCode')->from(CoreFrameworkEntity\Ticket::class, 't')
            ->leftJoin('t.supportLabels', 'sl')
            ->andWhere('t.id = :ticketId')
            ->setParameter('ticketId', $ticketId);

        $result = $qb->getQuery()->getResult();

        return $result ? $result : [];
    }

    public function getManualWorkflow()
    {
        $preparedResponseIds = [];
        $groupIds = [];
        $teamIds = [];
        $userId = $this->container->get('user.service')->getCurrentUser()->getAgentInstance()->getId();

        $preparedResponseRepo = $this->entityManager->getRepository(PreparedResponses::class)->findAll();

        foreach ($preparedResponseRepo as $pr) {
            if ($userId == $pr->getUser()->getId()) {
                //Save the ids of the saved reply.
                array_push($preparedResponseIds, (int)$pr->getId());
            }
        }

        // Get the ids of the Group(s) the current user is associated with.
        $query = "select * from uv_user_support_groups where userInstanceId =" . $userId;
        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        foreach ($result as $row) {
            array_push($groupIds, $row['supportGroupId']);
        }

        // Get all the saved reply's ids that is associated with the user's group(s).
        $query = "select * from uv_prepared_response_support_groups";
        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        foreach ($result as $row) {
            if (in_array($row['group_id'], $groupIds)) {
                array_push($preparedResponseIds, (int) $row['savedReply_id']);
            }
        }

        // Get the ids of the Team(s) the current user is associated with.
        $query = "select * from uv_user_support_teams";
        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        foreach ($result as $row) {
            if ($row['userInstanceId'] == $userId) {
                array_push($teamIds, $row['supportTeamId']);
            }
        }

        $query = "select * from uv_prepared_response_support_teams";
        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        foreach ($result as $row) {
            if (in_array($row['subgroup_id'], $teamIds)) {
                array_push($preparedResponseIds, (int)$row['savedReply_id']);
            }
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DISTINCT mw')
            ->from(PreparedResponses::class, 'mw')
            ->where('mw.status = 1')
            ->andWhere('mw.id IN (:ids)')
            ->setParameter('ids', $preparedResponseIds);

        return $qb->getQuery()->getResult();
    }

    public function getSavedReplies()
    {
        $savedReplyIds = [];
        $groupIds = [];
        $teamIds = [];
        $userInstance = $this->container->get('user.service')->getCurrentUser()->getAgentInstance();
        $userId = $userInstance->getId();
        $currentRole = $userInstance->getSupportRole()->getCode();

        $savedReplyRepo = $this->entityManager->getRepository(CoreFrameworkEntity\SavedReplies::class)->findAll();

        if (in_array($currentRole, ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN'])) {
            return $savedReplyRepo;
        }

        foreach ($savedReplyRepo as $sr) {
            if ($userId == $sr->getUser()->getId()) {
                //Save the ids of the saved reply.
                array_push($savedReplyIds, (int)$sr->getId());
            }
        }

        // Get the ids of the Group(s) the current user is associated with.
        $query = "select * from uv_user_support_groups where userInstanceId = :userId";
        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery(['userId' => $userId])->fetchAllAssociative();

        foreach ($result as $row) {
            array_push($groupIds, $row['supportGroupId']);
        }

        // Get all the saved reply's ids that is associated with the user's group(s).
        $query = "select * from uv_saved_replies_groups";
        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        foreach ($result as $row) {
            if (in_array($row['group_id'], $groupIds)) {
                array_push($savedReplyIds, (int) $row['savedReply_id']);
            }
        }

        // Get the ids of the Team(s) the current user is associated with.
        $query = "select * from uv_user_support_teams";
        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        foreach ($result as $row) {
            if ($row['userInstanceId'] == $userId) {
                array_push($teamIds, $row['supportTeamId']);
            }
        }

        $query = "select * from uv_saved_replies_teams";
        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        foreach ($result as $row) {
            if (in_array($row['subgroup_id'], $teamIds)) {
                array_push($savedReplyIds, (int)$row['savedReply_id']);
            }
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DISTINCT sr')
            ->from(CoreFrameworkEntity\SavedReplies::class, 'sr')
            ->Where('sr.id IN (:ids)')
            ->setParameter('ids', $savedReplyIds);

        return $qb->getQuery()->getResult();
    }

    public function getPriorities()
    {
        static $priorities;
        if (null !== $priorities)
            return $priorities;

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('tp')->from(CoreFrameworkEntity\TicketPriority::class, 'tp');

        return $priorities = $qb->getQuery()->getArrayResult();
    }

    public function getTicketLastThread($ticketId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("th")->from(CoreFrameworkEntity\Thread::class, 'th')
            ->leftJoin('th.ticket', 't')
            ->andWhere('t.id = :ticketId')
            ->setParameter('ticketId', $ticketId)
            ->orderBy('th.id', 'DESC');

        return $qb->getQuery()->setMaxResults(1)->getSingleResult();
    }

    public function getlastReplyAgentName($ticketId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("u.id,CONCAT(u.firstName,' ', u.lastName) AS name,u.firstName")->from(CoreFrameworkEntity\Thread::class, 'th')
            ->leftJoin('th.ticket', 't')
            ->leftJoin('th.user', 'u')
            ->leftJoin('u.userInstance', 'userInstance')
            ->andWhere('userInstance.supportRole != :roles')
            ->andWhere('t.id = :ticketId')
            ->andWhere('th.threadType = :threadType')
            ->setParameter('threadType', 'reply')
            ->andWhere('th.createdBy = :createdBy')
            ->setParameter('createdBy', 'agent')
            ->setParameter('ticketId', $ticketId)
            ->setParameter('roles', 4)
            ->orderBy('th.id', 'DESC');

        $result = $qb->getQuery()->setMaxResults(1)->getResult();

        return $result ? $result[0] : null;
    }

    public function getLastReply($ticketId, $userType = null)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select("th, a, u.id as userId")
            ->from(CoreFrameworkEntity\Thread::class, 'th')
            ->leftJoin('th.ticket', 't')
            ->leftJoin('th.attachments', 'a')
            ->leftJoin('th.user', 'u')
            ->andWhere('t.id = :ticketId')
            ->andWhere('th.threadType = :threadType')
            ->setParameter('threadType', 'reply')
            ->setParameter('ticketId', $ticketId)
            ->orderBy('th.id', 'DESC')
            ->getMaxResults(1);

        if (!empty($userType)) {
            $queryBuilder->andWhere('th.createdBy = :createdBy')->setParameter('createdBy', $userType);
        }

        $threadResponse = $queryBuilder->getQuery()->getArrayResult();

        if (!empty($threadResponse[0][0])) {
            $threadDetails = $threadResponse[0][0];
            $userService = $this->container->get('user.service');

            if ($threadDetails['createdBy'] == 'agent') {
                $threadDetails['user'] = $userService->getAgentDetailById($threadResponse[0]['userId']);
            } else {
                $threadDetails['user'] = $userService->getCustomerPartialDetailById($threadResponse[0]['userId']);
            }

            $threadDetails['reply'] = html_entity_decode($threadDetails['message']);
            $threadDetails['formatedCreatedAt'] = $this->timeZoneConverter($threadDetails['createdAt']);
            $threadDetails['timestamp'] = $userService->convertToDatetimeTimezoneTimestamp($threadDetails['createdAt']);

            if (!empty($threadDetails['attachments'])) {
                $entityManager = $this->entityManager;
                $uvdeskFileSystemService = $this->container->get('uvdesk.core.file_system.service');

                $threadDetails['attachments'] = array_map(function ($attachment) use ($entityManager, $uvdeskFileSystemService) {
                    $attachmentReferenceObject = $this->entityManager->getReference(CoreFrameworkEntity\Attachment::class, $attachment['id']);
                    return $uvdeskFileSystemService->getFileTypeAssociations($attachmentReferenceObject);
                }, $threadDetails['attachments']);
            }
        }

        return $threadDetails ?? null;
    }

    public function getSavedReplyContent($savedReplyId, $ticketId)
    {
        $ticket = $this->entityManager->getRepository(CoreFrameworkEntity\Ticket::class)->find($ticketId);
        $savedReply = $this->entityManager->getRepository(CoreFrameworkEntity\SavedReplies::class)->findOneById($savedReplyId);
        $savedReplyGroups = $savedReply->getSupportGroups()->toArray();
        $savedReplyTeams = $savedReply->getSupportTeams()->toArray();

        $savedReplyGroups = array_map(function ($group) {
            return $group->getId();
        }, $savedReplyGroups);

        $savedReplyTeams = array_map(function ($team) {
            return $team->getId();
        }, $savedReplyTeams);

        $userInstance = $this->container->get('user.service')->getCurrentUser()->getAgentInstance();
        $currentUserRole = $userInstance->getSupportRole()->getCode();

        // Check if the user belongs to any of the groups or teams associated with the saved reply
        $userGroups = $userInstance->getSupportGroups()->toArray();
        $userTeams = $userInstance->getSupportTeams()->toArray();

        $groupIds = array_map(function ($group) {
            return $group->getId();
        }, $userGroups);

        $teamIds = array_map(function ($team) {
            return $team->getId();
        }, $userTeams);

        if (!array_intersect($groupIds, $savedReplyGroups) && !array_intersect($teamIds, $savedReplyTeams) && (!in_array($currentUserRole, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']))) {
            throw new \Exception('You are not allowed to apply this saved reply.');
        }

        $emailPlaceholders = $this->getSavedReplyPlaceholderValues($ticket, 'customer');

        return $this->container->get('email.service')->processEmailContent($savedReply->getMessage(), $emailPlaceholders, true);
    }

    public function getSavedReplyPlaceholderValues($ticket, $type = "customer")
    {
        $variables = array();
        $variables['ticket.id'] = $ticket->getId();
        $variables['ticket.subject'] = $ticket->getSubject();

        $variables['ticket.status'] = $ticket->getStatus()->getCode();
        $variables['ticket.priority'] = $ticket->getPriority()->getCode();
        if ($ticket->getSupportGroup())
            $variables['ticket.group'] = $ticket->getSupportGroup()->getName();
        else
            $variables['ticket.group'] = '';

        $variables['ticket.team'] = ($ticket->getSupportTeam() ? $ticket->getSupportTeam()->getName() : '');

        $customer = $this->container->get('user.service')->getCustomerPartialDetailById($ticket->getCustomer()->getId());
        $variables['ticket.customerName'] = $customer['name'];
        $variables['ticket.customerEmail'] = $customer['email'];
        $userService = $this->container->get('user.service');

        $variables['ticket.agentName'] = '';
        $variables['ticket.agentEmail'] = '';
        if ($ticket->getAgent()) {
            $agent = $this->container->get('user.service')->getAgentDetailById($ticket->getAgent()->getId());
            if ($agent) {
                $variables['ticket.agentName'] = $agent['name'];
                $variables['ticket.agentEmail'] = $agent['email'];
            }
        }

        $router = $this->container->get('router');

        if ($type == 'customer') {
            $ticketListURL = $router->generate('helpdesk_customer_ticket_collection', [
                'id' => $ticket->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            $ticketListURL = $router->generate('helpdesk_member_ticket_collection', [
                'id' => $ticket->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $variables['ticket.link'] = sprintf("<a href='%s'>#%s</a>", $ticketListURL, $ticket->getId());

        return $variables;
    }

    public function isEmailBlocked($email, $website)
    {
        $flag = false;
        $email = strtolower($email);
        $knowledgeBaseWebsite = $this->entityManager->getRepository(KnowledgebaseWebsite::class)->findOneBy(['website' => $website->getId(), 'isActive' => 1]);
        $list = $this->container->get('user.service')->getWebsiteSpamDetails($knowledgeBaseWebsite);

        // Blacklist
        if (!empty($list['blackList']['email']) && in_array($email, $list['blackList']['email'])) {
            // Emails
            $flag = true;
        } elseif (!empty($list['blackList']['domain'])) {
            // Domains
            foreach ($list['blackList']['domain'] as $domain) {
                if (strpos($email, $domain)) {
                    $flag = true;
                    break;
                }
            }
        }

        // Whitelist
        if ($flag) {
            if (isset($email, $list['whiteList']['email']) && in_array($email, $list['whiteList']['email'])) {
                // Emails
                return false;
            } elseif (isset($list['whiteList']['domain'])) {
                // Domains
                foreach ($list['whiteList']['domain'] as $domain) {
                    if (strpos($email, $domain)) {
                        $flag = false;
                    }
                }
            }
        }

        return $flag;
    }

    public function timeZoneConverter($dateFlag)
    {
        $website = $this->entityManager->getRepository(CoreFrameworkEntity\Website::class)->findOneBy(['code' => 'Knowledgebase']);
        $timeZone = $website->getTimezone();
        $timeFormat = $website->getTimeformat();

        $activeUser = $this->container->get('user.service')->getSessionUser();
        $agentTimeZone = !empty($activeUser) ? $activeUser->getTimezone() : null;
        $agentTimeFormat = !empty($activeUser) ? $activeUser->getTimeformat() : null;

        $parameterType = gettype($dateFlag);
        if ($parameterType == 'string') {
            if (is_null($agentTimeZone) && is_null($agentTimeFormat)) {
                if (is_null($timeZone) && is_null($timeFormat)) {
                    $datePattern = date_create($dateFlag);

                    return date_format($datePattern, 'd-m-Y h:ia');
                } else {
                    $dateFlag = new \DateTime($dateFlag);
                    $datePattern = $dateFlag->setTimezone(new \DateTimeZone($timeZone));

                    return date_format($datePattern, $timeFormat);
                }
            } else {
                $dateFlag = new \DateTime($dateFlag);
                $datePattern = $dateFlag->setTimezone(new \DateTimeZone($agentTimeZone));

                return date_format($datePattern, $agentTimeFormat);
            }
        } else {
            if (is_null($agentTimeZone) && is_null($agentTimeFormat)) {
                if (is_null($timeZone) && is_null($timeFormat)) {
                    return date_format($dateFlag, 'd-m-Y h:ia');
                } else {
                    $datePattern = $dateFlag->setTimezone(new \DateTimeZone($timeZone));
                    return date_format($datePattern, $timeFormat);
                }
            } else {
                $datePattern = $dateFlag->setTimezone(new \DateTimeZone($agentTimeZone));

                return date_format($datePattern, $agentTimeFormat);
            }
        }
    }

    public function fomatTimeByPreference($dbTime, $timeZone, $timeFormat, $agentTimeZone, $agentTimeFormat)
    {
        if (is_null($agentTimeZone) && is_null($agentTimeFormat)) {
            if (is_null($timeZone) && is_null($timeFormat)) {
                $dateTimeZone = $dbTime;
                $timeFormatString = 'd-m-Y h:ia';
            } else {
                $dateTimeZone = $dbTime->setTimezone(new \DateTimeZone($timeZone));
                $timeFormatString = $timeFormat;
            }
        } else {
            $dateTimeZone = $dbTime->setTimezone(new \DateTimeZone($agentTimeZone));
            $timeFormatString = $agentTimeFormat;
        }

        $time['dateTimeZone'] = $dateTimeZone;
        $time['timeFormatString'] = $timeFormatString;

        return $time;
    }

    public function isTicketAccessGranted(CoreFrameworkEntity\Ticket $ticket, CoreFrameworkEntity\User $user = null, $firewall = 'members')
    {
        // @TODO: Take current firewall into consideration (access check on behalf of agent/customer)
        if (empty($user)) {
            $user = $this->container->get('user.service')->getSessionUser();
        }

        if (empty($user)) {
            return false;
        } else {
            $agentInstance = $user->getAgentInstance();

            if (empty($agentInstance)) {
                return false;
            }
        }

        if ($agentInstance->getSupportRole()->getId() == 3 && in_array($agentInstance->getTicketAccessLevel(), [2, 3, 4])) {
            $accessLevel = $agentInstance->getTicketAccessLevel();

            // Check if user has been given inidividual access
            if ($ticket->getAgent() != null && $ticket->getAgent()->getId() == $user->getId()) {
                return true;
            }

            if ($accessLevel == 2 || $accessLevel == 3) {
                // Check if user belongs to a support team assigned to ticket
                $teamReferenceIds = array_map(function ($team) {
                    return $team->getId();
                }, $agentInstance->getSupportTeams()->toArray());

                if ($ticket->getSupportTeam() != null && in_array($ticket->getSupportTeam()->getId(), $teamReferenceIds)) {
                    return true;
                } else if ($accessLevel == 2) {
                    // Check if user belongs to a support group assigned to ticket
                    $groupReferenceIds = array_map(function ($group) {
                        return $group->getId();
                    }, $agentInstance->getSupportGroups()->toArray());

                    if ($ticket->getSupportGroup() != null && in_array($ticket->getSupportGroup()->getId(), $groupReferenceIds)) {
                        return true;
                    }
                }
            }

            return false;
        }

        return true;
    }

    public function addTicketCustomFields($thread, $submittedCustomFields = [], $uploadedFilesCollection = [])
    {
        $customFieldsService = null;
        $customFieldsEntityReference = null;

        if ($this->userService->isFileExists('apps/uvdesk/custom-fields')) {
            $customFieldsService = $this->container->get('uvdesk_package_custom_fields.service');
            $customFieldsEntityReference = UVDeskCommunityPackages\CustomFields\Entity\CustomFields::class;
            $customFieldValuesEntityReference = UVDeskCommunityPackages\CustomFields\Entity\CustomFieldsValues::class;
            $ticketCustomFieldValuesEntityReference = UVDeskCommunityPackages\CustomFields\Entity\TicketCustomFieldsValues::class;
        } else if ($this->userService->isFileExists('apps/uvdesk/form-component')) {
            $customFieldsService = $this->container->get('uvdesk_package_form_component.service');
            $customFieldsEntityReference = UVDeskCommunityPackages\FormComponent\Entity\CustomFields::class;
            $customFieldValuesEntityReference = UVDeskCommunityPackages\FormComponent\Entity\CustomFieldsValues::class;
            $ticketCustomFieldValuesEntityReference = UVDeskCommunityPackages\FormComponent\Entity\TicketCustomFieldsValues::class;
        } else {
            return;
        }

        $ticket = $thread->getTicket();
        $customFieldsCollection = $this->entityManager->getRepository($customFieldsEntityReference)->findAll();
        $customFieldValuesEntityRepository = $this->entityManager->getRepository($customFieldValuesEntityReference);

        foreach ($customFieldsCollection as $customFields) {
            if (in_array($customFields->getFieldType(), ['select', 'checkbox', 'radio']) && !count($customFields->getCustomFieldValues())) {
                continue;
            }

            if (
                !empty($submittedCustomFields)
                && $customFields->getFieldType() != 'file'
                && isset($submittedCustomFields[$customFields->getId()])
            ) {
                // Check if custom field dependency criterias are fullfilled
                if (
                    count($customFields->getCustomFieldsDependency())
                    && !in_array($ticket->getType(), $customFields->getCustomFieldsDependency()->toArray())
                ) {
                    continue;
                }

                // Save ticket custom fields
                $ticketCustomField = new $ticketCustomFieldValuesEntityReference();
                $ticketCustomField
                    ->setTicket($ticket)
                    ->setTicketCustomFieldsValues($customFields)
                    ->setValue(json_encode($submittedCustomFields[$customFields->getId()]))
                ;

                if (in_array($customFields->getFieldType(), ['select', 'checkbox', 'radio'])) {
                    // Add custom field values mapping too
                    if (is_array($submittedCustomFields[$customFields->getId()])) {
                        foreach ($submittedCustomFields[$customFields->getId()] as $value) {
                            $ticketCustomFieldValues = $customFieldValuesEntityRepository->findOneBy([
                                'id'           => $value,
                                'customFields' => $customFields,
                            ]);

                            if (!empty($ticketCustomFieldValues)) {
                                $ticketCustomField
                                    ->setTicketCustomFieldValueValues($ticketCustomFieldValues);
                            }
                        }
                    } else {
                        $ticketCustomFieldValues = $customFieldValuesEntityRepository->findOneBy([
                            'id'           => $submittedCustomFields[$customFields->getId()],
                            'customFields' => $customFields,
                        ]);

                        if (!empty($ticketCustomFieldValues)) {
                            $ticketCustomField
                                ->setTicketCustomFieldValueValues($ticketCustomFieldValues);
                        }
                    }
                }

                $this->entityManager->persist($ticketCustomField);
                $this->entityManager->flush();
            } else if (
                !empty($uploadedFilesCollection)
                && isset($uploadedFilesCollection[$customFields->getId()])
            ) {
                // Upload files
                $path = '/custom-fields/ticket/' . $ticket->getId() . '/';
                $fileNames = $this->fileUploadService->uploadFile($uploadedFilesCollection[$customFields->getid()], $path, true);

                if (!empty($fileNames)) {
                    // Save files entry to attachment table
                    try {
                        $newFilesNames = $customFieldsService->addFilesEntryToAttachmentTable([$fileNames], $thread);

                        foreach ($newFilesNames as $value) {
                            // Save ticket custom fields
                            $ticketCustomField = new $ticketCustomFieldValuesEntityReference();
                            $ticketCustomField
                                ->setTicket($ticket)
                                ->setTicketCustomFieldsValues($customFields)
                                ->setValue(json_encode([
                                    'name' => $value['name'],
                                    'path' => $value['path'],
                                    'id'   => $value['id'],
                                ]))
                            ;

                            $this->entityManager->persist($ticketCustomField);
                            $this->entityManager->flush();
                        }
                    } catch (\Exception $e) {
                        // @TODO: Log execption message
                    }
                }
            }
        }
    }

    // return attachemnt for initial thread
    public function getInitialThread($ticketId)
    {
        $firstThread = null;
        $intialThread = $this->entityManager->getRepository(CoreFrameworkEntity\Thread::class)->findBy(['ticket' => $ticketId]);

        foreach ($intialThread as $key => $value) {
            if ($value->getThreadType() == "create") {
                $firstThread = $value;
            }
        }

        return $firstThread;
    }

    public function getTicketConditions()
    {
        $conditions = array(
            'ticket' => [
                ('mail') => array(
                    [
                        'lable' => ('from_mail'),
                        'value' => 'from_mail',
                        'match' => 'email'
                    ],
                    [
                        'lable' => ('to_mail'),
                        'value' => 'to_mail',
                        'match' => 'email'
                    ],
                ),
                ('API') => array(
                    [
                        'lable' => ('Domain'),
                        'value' => 'domain',
                        'match' => 'api'
                    ],
                    [
                        'lable' => ('Locale'),
                        'value' => 'locale',
                        'match' => 'api'
                    ],
                ),
                ('ticket') => array(
                    [
                        'lable' => ('subject'),
                        'value' => 'subject',
                        'match' => 'string'
                    ],
                    [
                        'lable' => ('description'),
                        'value' => 'description',
                        'match' => 'string'
                    ],
                    [
                        'lable' => ('subject_or_description'),
                        'value' => 'subject_or_description',
                        'match' => 'string'
                    ],
                    [
                        'lable' => ('priority'),
                        'value' => 'priority',
                        'match' => 'select'
                    ],
                    [
                        'lable' => ('type'),
                        'value' => 'type',
                        'match' => 'select'
                    ],
                    [
                        'lable' => ('status'),
                        'value' => 'status',
                        'match' => 'select'
                    ],
                    [
                        'lable' => ('source'),
                        'value' => 'source',
                        'match' => 'select'
                    ],
                    [
                        'lable' => ('created'),
                        'value' => 'created',
                        'match' => 'date'
                    ],
                    [
                        'lable' => ('agent'),
                        'value' => 'agent',
                        'match' => 'select'
                    ],
                    [
                        'lable' => ('group'),
                        'value' => 'group',
                        'match' => 'select'
                    ],
                    [
                        'lable' => ('team'),
                        'value' => 'team',
                        'match' => 'select'
                    ],
                ),
                ('customer') => array(
                    [
                        'lable' => ('customer_name'),
                        'value' => 'customer_name',
                        'match' => 'string'
                    ],
                    [
                        'lable' => ('customer_email'),
                        'value' => 'customer_email',
                        'match' => 'email'
                    ],
                ),
            ],
            'task' => [
                ('task') => array(
                    [
                        'lable' => ('subject'),
                        'value' => 'subject',
                        'match' => 'string'
                    ],
                    [
                        'lable' => ('description'),
                        'value' => 'description',
                        'match' => 'string'
                    ],
                    [
                        'lable' => ('subject_or_description'),
                        'value' => 'subject_or_description',
                        'match' => 'string'
                    ],
                    [
                        'lable' => ('priority'),
                        'value' => 'priority',
                        'match' => 'select'
                    ],
                    [
                        'lable' => ('stage'),
                        'value' => 'stage',
                        'match' => 'select'
                    ],
                    [
                        'lable' => ('created'),
                        'value' => 'created',
                        'match' => 'date'
                    ],
                    [
                        'lable' => ('agent_name'),
                        'value' => 'agent_name',
                        'match' => 'select'
                    ],
                    [
                        'lable' => ('agent_email'),
                        'value' => 'agent_email',
                        'match' => 'select'
                    ],
                ),
            ]
        );


        return $conditions;
    }

    public function getAgentMatchConditions()
    {
        return [
            'email' => array(
                [
                    'lable' => ('is'),
                    'value' => 'is'
                ],
                [
                    'lable' => ('isNot'),
                    'value' => 'isNot'
                ],
                [
                    'lable' => ('contains'),
                    'value' => 'contains'
                ],
                [
                    'lable' => ('notContains'),
                    'value' => 'notContains'
                ],
            ),
            'api' => array(
                [
                    'lable' => ('is'),
                    'value' => 'is'
                ],
                [
                    'lable' => ('contains'),
                    'value' => 'contains'
                ],
            ),
            'string' => array(
                [
                    'lable' => ('is'),
                    'value' => 'is'
                ],
                [
                    'lable' => ('isNot'),
                    'value' => 'isNot'
                ],
                [
                    'lable' => ('contains'),
                    'value' => 'contains'
                ],
                [
                    'lable' => ('notContains'),
                    'value' => 'notContains'
                ],
                [
                    'lable' => ('startWith'),
                    'value' => 'startWith'
                ],
                [
                    'lable' => ('endWith'),
                    'value' => 'endWith'
                ],
            ),
            'select' => array(
                [
                    'lable' => ('is'),
                    'value' => 'is'
                ],
            ),
            'date' => array(
                [
                    'lable' => ('before'),
                    'value' => 'before'
                ],
                [
                    'lable' => ('beforeOn'),
                    'value' => 'beforeOn'
                ],
                [
                    'lable' => ('after'),
                    'value' => 'after'
                ],
                [
                    'lable' => ('afterOn'),
                    'value' => 'afterOn'
                ],
            ),
            'datetime' => array(
                [
                    'lable' => ('before'),
                    'value' => 'beforeDateTime'
                ],
                [
                    'lable' => ('beforeOn'),
                    'value' => 'beforeDateTimeOn'
                ],
                [
                    'lable' => ('after'),
                    'value' => 'afterDateTime'
                ],
                [
                    'lable' => ('afterOn'),
                    'value' => 'afterDateTimeOn'
                ],
            ),
            'time' => array(
                [
                    'lable' => ('before'),
                    'value' => 'beforeTime'
                ],
                [
                    'lable' => ('beforeOn'),
                    'value' => 'beforeTimeOn'
                ],
                [
                    'lable' => ('after'),
                    'value' => 'afterTime'
                ],
                [
                    'lable' => ('afterOn'),
                    'value' => 'afterTimeOn'
                ],
            ),
            'number' => array(
                [
                    'lable' => ('is'),
                    'value' => 'is'
                ],
                [
                    'lable' => ('isNot'),
                    'value' => 'isNot'
                ],
                [
                    'lable' => ('contains'),
                    'value' => 'contains'
                ],
                [
                    'lable' => ('greaterThan'),
                    'value' => 'greaterThan'
                ],
                [
                    'lable' => ('lessThan'),
                    'value' => 'lessThan'
                ],
            ),
        ];
    }

    public function getTicketMatchConditions()
    {
        return [
            'email' => array(
                [
                    'lable' => ('is'),
                    'value' => 'is'
                ],
                [
                    'lable' => ('isNot'),
                    'value' => 'isNot'
                ],
                [
                    'lable' => ('contains'),
                    'value' => 'contains'
                ],
                [
                    'lable' => ('notContains'),
                    'value' => 'notContains'
                ],
            ),
            'api' => array(
                [
                    'lable' => ('is'),
                    'value' => 'is'
                ],
                [
                    'lable' => ('contains'),
                    'value' => 'contains'
                ],
            ),
            'string' => array(
                [
                    'lable' => ('is'),
                    'value' => 'is'
                ],
                [
                    'lable' => ('isNot'),
                    'value' => 'isNot'
                ],
                [
                    'lable' => ('contains'),
                    'value' => 'contains'
                ],
                [
                    'lable' => ('notContains'),
                    'value' => 'notContains'
                ],
                [
                    'lable' => ('startWith'),
                    'value' => 'startWith'
                ],
                [
                    'lable' => ('endWith'),
                    'value' => 'endWith'
                ],
            ),
            'select' => array(
                [
                    'lable' => ('is'),
                    'value' => 'is'
                ],
                [
                    'lable' => ('isNot'),
                    'value' => 'isNot'
                ],
            ),
            'date' => array(
                [
                    'lable' => ('before'),
                    'value' => 'before'
                ],
                [
                    'lable' => ('beforeOn'),
                    'value' => 'beforeOn'
                ],
                [
                    'lable' => ('after'),
                    'value' => 'after'
                ],
                [
                    'lable' => ('afterOn'),
                    'value' => 'afterOn'
                ],
            ),
            'datetime' => array(
                [
                    'lable' => ('before'),
                    'value' => 'beforeDateTime'
                ],
                [
                    'lable' => ('beforeOn'),
                    'value' => 'beforeDateTimeOn'
                ],
                [
                    'lable' => ('after'),
                    'value' => 'afterDateTime'
                ],
                [
                    'lable' => ('afterOn'),
                    'value' => 'afterDateTimeOn'
                ],
            ),
            'time' => array(
                [
                    'lable' => ('before'),
                    'value' => 'beforeTime'
                ],
                [
                    'lable' => ('beforeOn'),
                    'value' => 'beforeTimeOn'
                ],
                [
                    'lable' => ('after'),
                    'value' => 'afterTime'
                ],
                [
                    'lable' => ('afterOn'),
                    'value' => 'afterTimeOn'
                ],
            ),
            'number' => array(
                [
                    'lable' => ('is'),
                    'value' => 'is'
                ],
                [
                    'lable' => ('isNot'),
                    'value' => 'isNot'
                ],
                [
                    'lable' => ('contains'),
                    'value' => 'contains'
                ],
                [
                    'lable' => ('greaterThan'),
                    'value' => 'greaterThan'
                ],
                [
                    'lable' => ('lessThan'),
                    'value' => 'lessThan'
                ],
            ),
        ];
    }

    public function getTargetAction()
    {
        return [
            '4' => ['response' => ['time' => '2', 'unit' => 'hours'], 'resolve' => ['time' => '8', 'unit' => 'hours'], 'operational' => 'calendarHours', 'isActive' => 'on'],
            '3' => ['response' => ['time' => '4', 'unit' => 'hours'], 'resolve' => ['time' => '1', 'unit' => 'days'], 'operational' => 'calendarHours', 'isActive' => 'on'],
            '2' => ['response' => ['time' => '8', 'unit' => 'hours'], 'resolve' => ['time' => '3', 'unit' => 'days'], 'operational' => 'calendarHours', 'isActive' => 'on'],
            '1' => ['response' => ['time' => '16', 'unit' => 'hours'], 'resolve' => ['time' => '5', 'unit' => 'days'], 'operational' => 'calendarHours', 'isActive' => 'on'],
        ];
    }

    public function getTicketActions($force = false)
    {
        $actionArray =  array(
            'ticket' => [
                'priority'               => ('action.priority'),
                'type'                   => ('action.type'),
                'status'                 => ('action.status'),
                'tag'                    => ('action.tag'),
                'note'                   => ('action.note'),
                'label'                  => ('action.label'),
                'assign_agent'           => ('action.assign_agent'),
                'assign_group'           => ('action.assign_group'),
                'assign_team'            => ('action.assign_team'),
                'mail_agent'             => ('action.mail_agent'),
                'mail_group'             => ('action.mail_group'),
                'mail_team'              => ('action.mail_team'),
                'mail_customer'          => ('action.mail_customer'),
                'mail_last_collaborator' => ('action.mail_last_collaborator'),
                'mail_all_collaborators' => ('action.mail_all_collaborators'),
                'delete_ticket'          => ('action.delete_ticket'),
                'mark_spam'              => ('action.mark_spam'),
            ],
            'task' => [
                'reply'            => ('action.reply'),
                'mail_agent'       => ('action.mail_agent'),
                'mail_members'     => ('action.mail_members'),
                'mail_last_member' => ('action.mail_last_member'),
            ],
            'customer' => [
                'mail_customer' => ('action.mail_customer'),
            ],
            'agent' => [
                'mail_agent'    => ('action.mail_agent'),
                'task_transfer' => ('action.task_transfer'),
                'assign_agent'  => ('action.assign_agent'),
                'assign_group'  => ('action.assign_group'),
                'assign_team'   => ('action.assign_team'),
            ],
        );

        $actionRoleArray = [
            'ticket->priority'               => 'ROLE_AGENT_UPDATE_TICKET_PRIORITY',
            'ticket->type'                   => 'ROLE_AGENT_UPDATE_TICKET_TYPE',
            'ticket->status'                 => 'ROLE_AGENT_UPDATE_TICKET_STATUS',
            'ticket->tag'                    => 'ROLE_AGENT_ADD_TAG',
            'ticket->note'                   => 'ROLE_AGENT_ADD_NOTE',
            'ticket->assign_agent'           => 'ROLE_AGENT_ASSIGN_TICKET',
            'ticket->assign_group'           => 'ROLE_AGENT_ASSIGN_TICKET_GROUP',
            'ticket->assign_team'            => 'ROLE_AGENT_ASSIGN_TICKET_GROUP',
            'ticket->mail_agent'             => 'ROLE_AGENT',
            'ticket->mail_group'             => 'ROLE_AGENT_MANAGE_GROUP',
            'ticket->mail_team'              => 'ROLE_AGENT_MANAGE_SUB_GROUP',
            'ticket->mail_customer'          => 'ROLE_AGENT',
            'ticket->mail_last_collaborator' => 'ROLE_AGENT',
            'ticket->mail_all_collaborators' => 'ROLE_AGENT',
            'ticket->delete_ticket'          => 'ROLE_AGENT_DELETE_TICKET',
            'ticket->mark_spam'              => 'ROLE_AGENT_UPDATE_TICKET_STATUS',
            'ticket->label'                  => 'ROLE_ADMIN',
            'task->reply'                    => 'ROLE_AGENT',
            'task->mail_agent'               => 'ROLE_AGENT',
            'task->mail_members'             => 'ROLE_AGENT',
            'task->mail_last_member'         => 'ROLE_AGENT',
            'customer->mail_customer'        => 'ROLE_AGENT',
            'agent->mail_agent'              => 'ROLE_AGENT',
            'agent->task_transfer'           => 'ROLE_AGENT_EDIT_TASK',
            'agent->assign_agent'            => 'ROLE_AGENT_ASSIGN_TICKET',
            'agent->assign_group'            => 'ROLE_AGENT_ASSIGN_TICKET_GROUP',
            'agent->assign_team'             => 'ROLE_AGENT_ASSIGN_TICKET_GROUP',
        ];

        $resultArray = [];

        foreach ($actionRoleArray as $action => $role) {
            if ($role == 'ROLE_AGENT' || $this->container->get('user.service')->checkPermission($role) || $force) {
                $actionPath = explode('->', $action);
                $resultArray[$actionPath[0]][$actionPath[1]] = $actionArray[$actionPath[0]][$actionPath[1]];
            }
        }

        $repo = $this->container->get('doctrine.orm.entity_manager')->getRepository('WebkulAppBundle:ECommerceChannel');

        $ecomArray = [];
        $ecomChannels = $repo->getActiveChannelsByCompany($this->container->get('user.service')->getCurrentCompany());

        foreach ($ecomChannels as $channel) {
            $ecomArray['add_order_to_' . $channel['id']] = ('Add order to: ') . $channel['title'];
        }

        $resultArray['ticket'] = array_merge($resultArray['ticket'], $ecomArray);

        return $resultArray;
    }

    /**
     * Generates a unique url that can be used to access ticket without any active session with read only priviliges.
     */
    public function generateTicketCustomerReadOnlyResourceAccessLink(CoreFrameworkEntity\Ticket $ticket)
    {
        $customer = $ticket->getCustomer();

        $router = $this->container->get('router');

        $token = $this->generateCustomToken($ticket->getId());

        $publicResourceAccessLink = new CoreFrameworkEntity\PublicResourceAccessLink();
        $publicResourceAccessLink
            ->setResourceId($ticket->getId())
            ->setResourceType(CoreFrameworkEntity\Ticket::class)
            ->setUniqueResourceAccessId($token)
            ->setTotalViews(0)
            ->setCreatedAt(new \DateTime('now'))
            ->setExpiresAt(new \DateTime('+1 week'))
            ->setIsExpired(false)
            ->setUser($customer)
        ;

        $this->entityManager->persist($publicResourceAccessLink);
        $this->entityManager->flush();

        return $router->generate('helpdesk_customer_public_resource_access_intermediate', [
            'urid' => $publicResourceAccessLink->getUniqueResourceAccessId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    // Generate a token based on ticket, timestamp and random string.
    public function generateCustomToken($ticketId, $randomLength = 6)
    {
        // Convert the current timestamp to Base36
        $timestamp = base_convert(time(), 10, 36);

        // Add the ticket ID
        $ticketPart = strtoupper(base_convert($ticketId, 10, 36)); // Convert ticket ID to Base36 (uppercase for readability)

        // Generate a random string of specified length
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $randomLength; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Combine all parts: timestamp + ticket ID + random string
        return $timestamp . $ticketPart . $randomString;
    }

    public function sanitizeMessage($html)
    {
        // Step 1: Normalize the input by decoding multiple levels of encoding
        $originalHtml = $html;
        $iterations = 0;
        $maxIterations = 5; // Prevent infinite loops

        do {
            $previousHtml = $html;
            // Decode HTML entities (handles &lt; &gt; &amp; &quot; &#39; etc.)
            $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Also decode numeric entities like &#60; &#62;
            $html = preg_replace_callback('/&#(\d+);/', function ($matches) {
                return chr($matches[1]);
            }, $html);
            // Decode hex entities like &#x3C; &#x3E;
            $html = preg_replace_callback('/&#x([0-9a-fA-F]+);/', function ($matches) {
                return chr(hexdec($matches[1]));
            }, $html);
            $iterations++;
        } while ($html !== $previousHtml && $iterations < $maxIterations);

        // Step 2: Remove all script tags and their content (multiple variations)
        $scriptPatterns = [
            '/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is',
            '/<\s*script[^>]*>.*?$/is', // Unclosed script tags
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:\s*text\/html/i',
            '/data\s*:\s*application\/javascript/i'
        ];

        foreach ($scriptPatterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        // Step 3: Remove dangerous tags
        $dangerousTags = [
            'script',
            'iframe',
            'object',
            'embed',
            'form',
            'input',
            'textarea',
            'button',
            'select',
            'option',
            'style',
            'link',
            'meta',
            'base',
            'applet',
            'bgsound',
            'blink',
            'body',
            'frame',
            'frameset',
            'head',
            'html',
            'ilayer',
            'layer',
            'plaintext',
            'title',
            'xml',
            'xmp'
        ];

        foreach ($dangerousTags as $tag) {
            // Remove opening and closing tags
            $html = preg_replace('/<\s*\/?\s*' . preg_quote($tag, '/') . '[^>]*>/is', '', $html);
        }

        // Step 4: Remove ALL event handlers and dangerous attributes
        $dangerousAttributes = [
            // Event handlers
            'on\w+', // Catches onclick, onload, onerror, etc.
            // Other dangerous attributes
            'style',
            'background',
            'dynsrc',
            'lowsrc',
            'href\s*=\s*["\']?\s*javascript',
            'src\s*=\s*["\']?\s*javascript',
            'action',
            'formaction',
            'poster',
            'cite',
            'longdesc',
            'profile',
            'usemap'
        ];

        foreach ($dangerousAttributes as $attr) {
            $html = preg_replace('/\s+' . $attr . '\s*=\s*(["\'])[^"\']*\1/i', '', $html);
            $html = preg_replace('/\s+' . $attr . '\s*=\s*[^\s>]+/i', '', $html);
        }

        // Step 5: Remove dangerous protocols from remaining attributes
        $html = preg_replace('/(href|src|action|formaction|background|cite|longdesc|profile|usemap)\s*=\s*(["\']?)(javascript|data|vbscript|about|chrome|file|ftp|jar|mailto|ms-its|mhtml|mocha|opera|res|resource|shell|view-source|wyciwyg):[^"\'>\s]*/i', '$1=$2#blocked', $html);

        // Step 6: Strip tags - only allow explicitly safe ones
        $allowedTags = '<p><b><i><strong><em><ul><ol><li><br><a><img><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span><div>';
        $html = strip_tags($html, $allowedTags);

        // Step 7: Final validation and cleanup of remaining tags
        $html = preg_replace_callback('/<(\w+)([^>]*)>/i', function ($matches) {
            $tag = strtolower($matches[1]);
            $attributes = $matches[2];

            // Only allow specific attributes for specific tags
            $allowedAttributes = [
                'a'    => ['href', 'title', 'target'],
                'img'  => ['src', 'alt', 'width', 'height', 'title'],
                'p'    => ['class'],
                'div'  => ['class'],
                'span' => ['class']
            ];

            if (!isset($allowedAttributes[$tag])) {
                // Tag has no allowed attributes, return just the tag
                return '<' . $tag . '>';
            }

            $validAttrs = [];
            foreach ($allowedAttributes[$tag] as $allowedAttr) {
                if (preg_match('/\s+' . preg_quote($allowedAttr, '/') . '\s*=\s*(["\'])([^"\']*)\1/i', $attributes, $match)) {
                    $value = $match[2];

                    // Additional validation for specific attributes
                    if ($allowedAttr === 'href') {
                        // Only allow safe URLs
                        if (preg_match('/^(https?:\/\/|mailto:|\/|#)/i', $value)) {
                            $validAttrs[] = $allowedAttr . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                        }
                    } elseif ($allowedAttr === 'src') {
                        // Only allow safe image sources
                        if (preg_match('/^(https?:\/\/|\/)/i', $value)) {
                            $validAttrs[] = $allowedAttr . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                        }
                    } elseif ($allowedAttr === 'target') {
                        // Only allow safe target values
                        if (in_array($value, ['_blank', '_self', '_parent', '_top'])) {
                            $validAttrs[] = $allowedAttr . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                        }
                    } else {
                        // For other attributes, just escape the value
                        $validAttrs[] = $allowedAttr . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                    }
                }
            }

            return '<' . $tag . (empty($validAttrs) ? '' : ' ' . implode(' ', $validAttrs)) . '>';
        }, $html);

        // Step 8: Remove any malformed or incomplete tags
        $html = preg_replace('/<[^>]*$/', '', $html); // Remove incomplete tags at end
        $html = preg_replace('/^[^<]*>/', '', $html); // Remove incomplete tags at start

        // Step 9: Final security check - remove any remaining suspicious patterns
        $suspiciousPatterns = [
            '/expression\s*\(/i',           // CSS expression()
            '/behavior\s*:/i',              // CSS behavior
            '/binding\s*:/i',               // CSS binding
            '/import\s*["\']?[^"\';]*;?/i', // CSS @import
            '/url\s*\([^)]*\)/i',          // CSS url()
            '/&#/i',                        // Remaining HTML entities
            '/&\w+;/i'                      // HTML entity patterns
        ];

        foreach ($suspiciousPatterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        // Step 10: Trim and return
        return trim($html);
    }

    public function getThreadAttachments($thread)
    {
        $attachments = $this->entityManager->getRepository(CoreFrameworkEntity\Attachment::class)->findBy(['thread' => $thread->getId()]);

        $attachmentCollection = array_map(function ($attachment) {
            return [
                'id'                => $attachment->getId(),
                'name'              => $attachment->getName(),
                'contentType'       => $attachment->getContentType(),
                'path'              => $attachment->getPath(),
                'fileSystem'        => $attachment->getFileSystem(),
                'attachmentThumb'   => $attachment->getAttachmentThumb(),
                'attachmentOrginal' => $attachment->getAttachmentOrginal(),
            ];
        }, $attachments);

        return $attachmentCollection;
    }

    public function sendWebhookNotificationAction($thread)
    {
        $website = $this->entityManager->getRepository(Website::class)->findOneByCode('helpdesk');
        $endpoint = $website->getWebhookUrl();
        $customFields = [];

        if (empty($endpoint)) {
            return;
        }

        if ($thread->getThreadType() == 'reply' || $thread->getThreadType() == 'create') {
            $ticket = $thread->getTicket();

            if ($this->userService->isFileExists('apps/uvdesk/custom-fields')) {
                $customFieldsService = $this->container->get('uvdesk_package_custom_fields.service');
                $customFields =  $customFieldsService->getTicketCustomFieldDetails($ticket->getId());
            }

            $ticketStatus = [
                'id'    => $ticket->getStatus()->getId(),
                'code'  => $ticket->getStatus()->getCode(),
            ];

            $payload = json_encode([
                'threadDetails' => [
                    'threadId'          => $thread->getId(),
                    'threadReply'       => strip_tags(html_entity_decode($thread->getMessage())),
                    'ticketId'          => $ticket->getId(),
                    'customerEmail'     => $ticket->getCustomer()->getEmail(),
                    'userType'          => $thread->getCreatedBy(),
                    'agentEmail'        => $thread->getCreatedBy() === 'agent' ? $thread->getUser()->getEmail() : '',
                    'createdAt'         => $thread->getCreatedAt(),
                    'source'            => $thread->getSource(),
                    'threadAttachments' => $this->getThreadAttachments($thread),
                    'status'            => $ticketStatus,
                ],
                'customFields' => $customFields
            ]);

            $curlHandler = curl_init();

            curl_setopt($curlHandler, CURLOPT_URL, $endpoint);
            curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($curlHandler, CURLOPT_POST, true);
            curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $payload);

            $curlResponse = curl_exec($curlHandler);

            curl_close($curlHandler);
        }

        return;
    }
}

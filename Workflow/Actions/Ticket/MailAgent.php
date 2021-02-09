<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;

class MailAgent extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.mail_agent';
    }

    public static function getDescription()
    {
        return "Mail to agent";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }

    public static function getOptions(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $emailTemplateCollection = array_map(function ($emailTemplate) {
            return [
                'id' => $emailTemplate->getId(),
                'name' => $emailTemplate->getName(),
            ];
        }, $entityManager->getRepository('UVDeskCoreFrameworkBundle:EmailTemplates')->findAll());

        $agentCollection = array_map(function ($agent) {
            return [
                'id' => $agent['id'],
                'name' => $agent['name'],
            ];
        }, $container->get('user.service')->getAgentPartialDataCollection());

        array_unshift($agentCollection, [
            'id' => 'responsePerforming',
            'name' => 'Response Performing Agent',
        ], [
            'id' => 'assignedAgent',
            'name' => 'Assigned Agent',
        ]);

        return [
            'partResults' => $agentCollection,
            'templates' => $emailTemplateCollection,
        ];
    }

    public static function applyAction(ContainerInterface $container, $entity, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        if ($entity instanceof Ticket) {
            $emailTemplate = $entityManager->getRepository('UVDeskCoreFrameworkBundle:EmailTemplates')->findOneById($value['value']);
            $emails = self::getAgentMails($value['for'], (($ticketAgent = $entity->getAgent()) ? $ticketAgent->getEmail() : ''), $container);
            
            if ($emails && $emailTemplate) {
                $queryBuilder = $entityManager->createQueryBuilder()
                    ->select('th.messageId as messageId')
                    ->from('UVDeskCoreFrameworkBundle:Thread', 'th')
                    ->where('th.createdBy = :userType')->setParameter('userType', 'agent')
                    ->orderBy('th.id', 'DESC')
                    ->setMaxResults(1);
                
                $inReplyTo = $queryBuilder->getQuery()->getOneOrNullResult();

                if (!empty($inReplyTo)) {
                    $emailHeaders['In-Reply-To'] = $inReplyTo;
                }

                if (!empty($entity->getReferenceIds())) {
                    $emailHeaders['References'] = $entity->getReferenceIds();
                }

                // Only process attachments if required in the message body
                // @TODO: Revist -> Maybe we should always include attachments if they are provided??
                if (!empty($entity->createdThread) && strpos($emailTemplate->getMessage(), '{%ticket.attachments%}') !== false || strpos($emailTemplate->getMessage(), '{% ticket.attachments %}') !== false) {
                    $attachments = array_map(function($attachment) use ($container) { 
                        return [
                            'name' => $attachment['name'],
                            'path' => str_replace('//', '/', $container->get('kernel')->getProjectDir() . "/public" . $attachment['relativePath']),
                        ];
                    }, $entity->createdThread->getAttachments()->toArray());
                }

                $placeHolderValues = $container->get('email.service')->getTicketPlaceholderValues($entity, 'agent');
                $subject = $container->get('email.service')->processEmailSubject($emailTemplate->getSubject(), $placeHolderValues);
                $message = $container->get('email.service')->processEmailContent($emailTemplate->getMessage(), $placeHolderValues);
                
                foreach ($emails as $email) {
                    $messageId = $container->get('email.service')->sendMail($subject, $message, $email, $emailHeaders, null, $attachments ?? []);
                }
            } else {
                // Email Template/Emails Not Found. Disable Workflow/Prepared Response
                // $this->disableEvent($event, $entity);
            }
        } 
    }

    public static function getAgentMails($for, $currentEmails, $container)
    {
        $agentMails = [];
        $entityManager = $container->get('doctrine.orm.entity_manager');

        foreach ($for as $agent) {
            if ($agent == 'assignedAgent') {
                if (is_array($currentEmails)) {
                    $agentMails = array_merge($agentMails, $currentEmails);
                } else {
                    $agentMails[] = $currentEmails;
                }
            } else if ($agent == 'responsePerforming' && is_object($currentUser = $container->get('security.token_storage')->getToken()->getUser())) {
                // Add current user email if any
                $agentMails[] = $currentUser->getEmail();
            } else if ($agent == 'baseAgent') {
                // Add selected user email if any
                if (is_array($currentEmails)) {
                    $agentMails = array_merge($agentMails, $currentEmails);
                } else {
                    $agentMails[] = $currentEmails;
                }
            } else if((int)$agent) {
                $qb = $entityManager->createQueryBuilder();
                $email = $qb->select('u.email')->from('UVDeskCoreFrameworkBundle:User', 'u')
                    ->andwhere("u.id = :userId")
                    ->setParameter('userId', $agent)
                    ->getQuery()->getResult();
                
                if (isset($email[0]['email'])) {
                    $agentMails[] = $email[0]['email'];
                }
            }
        }

        return array_filter($agentMails);
    }
}

<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\EmailTemplates;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Attachment;
use Webkul\UVDesk\AutomationBundle\Workflow\Event;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class MailTeam extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.mail_team';
    }

    public static function getDescription()
    {
        return "Mail to team";
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
        }, $entityManager->getRepository(EmailTemplates::class)->findAll());

        $supportTeamCollection = array_map(function ($supportTeam) {
            return [
                'id' => $supportTeam['id'],
                'name' => $supportTeam['name'],
            ];
        }, $container->get('user.service')->getSupportTeams());

        array_unshift($supportTeamCollection, [
            'id' => 'assignedTeam',
            'name' => 'Assigned Team',
        ]);

        return [
            'partResults' => $supportTeamCollection,
            'templates' => $emailTemplateCollection,
        ];
    }

    public static function applyAction(ContainerInterface $container, Event $event, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        if (!$event instanceof TicketActivity) {
            return;
        } else {
            $ticket = $event->getTicket();
            $emailTemplate = $entityManager->getRepository(EmailTemplates::class)->findOneById($value['value']);
            
            if (empty($ticket) || empty($emailTemplate)) {
                return;
            }
        }
        
        $createThread = $container->get('ticket.service')->getCreateReply($ticket->getId(), false);
        
        $mailData = array();
        $mailData['references'] = $createThread['messageId'];

        $createdThread = isset($ticket->createdThread) && $ticket->createdThread->getThreadType() != "note" ? $ticket->createdThread : (isset($ticket->currentThread) ? $ticket->currentThread : "") ;
        $attachments = [];

        if (!empty($createdThread) && (strpos($emailTemplate->getMessage(), '{%ticket.attachments%}') !== false || strpos($emailTemplate->getMessage(), '{% ticket.attachments %}') !== false)) {
            $attachments = array_map(function($attachment) use ($container) { 
                return str_replace('//', '/', $container->get('kernel')->getProjectDir() . "/public" . $attachment->getPath());
            }, $entityManager->getRepository(Attachment::class)->findByThread($createdThread));
        }

        $to = array();
        
        foreach ($value['for'] as $subGrp) {
            foreach ($container->get('user.service')->getUsersBySubGroupId((($subGrp == 'assignedTeam' && $ticket->getSupportTeam()) ? $ticket->getSupportTeam()->getId() : $subGrp)) as $agent) {
                $to[] = $agent['email'];
            }
        }

        if (count($to)) {
            $mailData['email'] = $to;
            $placeHolderValues = $container->get('email.service')->getTicketPlaceholderValues($ticket);
            $subject = $container->get('email.service')->processEmailSubject($emailTemplate->getSubject(),$placeHolderValues);
            $message = $container->get('email.service')->processEmailContent($emailTemplate->getMessage(),$placeHolderValues);

            foreach ($mailData['email'] as $email) {
                $messageId = $container->get('email.service')->sendMail($subject, $message, $email, [], null, $attachments ?? []);
            }
        }
    }
}

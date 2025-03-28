<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\EmailTemplates;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Attachment;
use Webkul\UVDesk\AutomationBundle\Workflow\Event;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class MailCustomer extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.mail_customer';
    }

    public static function getDescription()
    {
        return "Mail to customer";
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

        return $emailTemplateCollection;
    }

    public static function applyAction(ContainerInterface $container, Event $event, $value = null, $thread = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        if (!$event instanceof TicketActivity) {
            return;
        } else {
            $ticket = $event->getTicket();
            
            if (empty($ticket)) {
                return;
            }
        }

        $currentThread = isset($ticket->currentThread) ? $ticket->currentThread : '';
        $createdThread = isset($ticket->createdThread) ? $ticket->createdThread : '';
        
        $emailTemplate = $entityManager->getRepository(EmailTemplates::class)->findOneById($value);

        if (empty($emailTemplate)) {
            return;
        }

        // Only process attachments if required in the message body
        // @TODO: Revist -> Maybe we should always include attachments if they are provided??
        $attachments = [];
        if (!empty($createdThread) && (strpos($emailTemplate->getMessage(), '{%ticket.attachments%}') !== false || strpos($emailTemplate->getMessage(), '{% ticket.attachments %}') !== false)) {
            $attachments = array_map(function($attachment) use ($container) { 
                return str_replace('//', '/', $container->get('kernel')->getProjectDir() . "/public" . $attachment->getPath());
            }, $entityManager->getRepository(Attachment::class)->findByThread($createdThread));
        }

        $ticketPlaceholders = $container->get('email.service')->getTicketPlaceholderValues($ticket);
        $subject = $container->get('email.service')->processEmailSubject($emailTemplate->getSubject(), $ticketPlaceholders);
        $message = $container->get('email.service')->processEmailContent($emailTemplate->getMessage(), $ticketPlaceholders);
        $thread = ($thread != null) ? $thread : $createdThread;
        $ticketCollaborators = (($thread != null) && !empty($thread->getTicket()) && $thread != "" ) ? $thread->getTicket()->getCollaborators() : [];

        $headers = ['References' => $ticket->getReferenceIds()]; 
        if (!empty($thread)) {
            $headers = ['References' => $ticket->getReferenceIds()];
        
            if (!empty($currentThread) && null != $currentThread->getMessageId()) {
                $headers['In-Reply-To'] = $currentThread->getMessageId();
            }

            $messageId = $container->get('email.service')->sendMail($subject, $message, $ticket->getCustomer()->getEmail(), $headers, $ticket->getMailboxEmail(), $attachments ?? []);

            if (!empty($messageId)) {
                $updatedReferenceIds = $ticket->getReferenceIds() . ' ' . $messageId;            
                $ticket->setReferenceIds($updatedReferenceIds);

                $entityManager->persist($ticket);
                $entityManager->flush();
            }

            if($thread->getCc() || $thread->getBcc() || $ticketCollaborators != null && count($ticketCollaborators) > 0) {
                self::sendCcBccMail($container, $ticket, $thread, $subject, $attachments, $ticketCollaborators, $message);
            }
            
        } else {
            if (!empty($ticket->getReferenceIds())) {
                $headers = ['References' => $ticket->getReferenceIds()];
            }
            
            $message = $container->get('email.service')->sendMail($subject, $message, $ticket->getCustomer()->getEmail(), $headers);
        }
    }

    public static function sendCcBccMail($container, $ticket, $thread, $subject, $attachments, $ticketCollaborators, $message = null)
    {
        $cc = array();
        $collabrator = array();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        if($thread->getCc() != null){
            foreach($thread->getCc() as $EmailCC){
                if ($entityManager->getRepository(Ticket::class)->isTicketCollaborator($thread->getTicket(), $EmailCC) != false) {
                    $collabrator[] = $EmailCC;
                } else {
                    $cc[] = $EmailCC;
                }
           }   
        }

        $emailOfcollabrator = !empty($thread) && $thread->getCreatedBy() == "collaborator" ? $thread->getUser()->getEmail() : null;
        if ($collabrator != null && !empty($collabrator) || $ticketCollaborators != null && !empty($ticketCollaborators)) {
            if (count($collabrator) == 0 && count($ticketCollaborators) > 0 && !empty($ticketCollaborators) && empty($collabrator)) {
                foreach ($ticketCollaborators as $collaborator) {
                    if (!empty($collaborator->getEmail()) && $collaborator->getEmail() != $emailOfcollabrator) {
                        $collabrator[] = $collaborator->getEmail();
                    }
                }
            }

            $messageId = $container->get('email.service')->sendMail($subject, $message, null, [], $ticket->getMailboxEmail(), $attachments ?? [], $collabrator ?? [], []);

            if (!empty($messageId)) {
                $updatedReferenceIds = $ticket->getReferenceIds() . ' ' . $messageId;            
                $ticket->setReferenceIds($updatedReferenceIds);

                $entityManager->persist($ticket);
                $entityManager->flush();
            }

            if ($collabrator != null && $thread->getCc()!= null && count($thread->getCc()) == count($collabrator) && $thread->getBcc() != null){
                $message = '<html><body style="background-image: none"><p>'.html_entity_decode($thread->getMessage()).'</p></body></html>';
                $messageId = $container->get('email.service')->sendMail($subject, $message, null, [], $ticket->getMailboxEmail(), $attachments ?? [], [], $thread->getBcc() ?? []);  
            }
        }

        if ($cc != null && !empty($cc)) {
            $message = '<html><body style="background-image: none"><p>'.html_entity_decode($thread->getMessage()).'</p></body></html>';
            $messageId = $container->get('email.service')->sendMail($subject, $message, null, [], $ticket->getMailboxEmail(), $attachments ?? [], $cc ?? [], $thread->getBcc() ?? []);    
        }
           
        if ($thread->getBcc() != null && $thread->getCc() == null) {
            $message = '<html><body style="background-image: none"><p>'.html_entity_decode($thread->getMessage()).'</p></body></html>';
            $messageId = $container->get('email.service')->sendMail($subject, $message, null, [], $ticket->getMailboxEmail(), $attachments ?? [], $thread->getCc() ?? [], $thread->getBcc() ?? []);  
        }
    }
}

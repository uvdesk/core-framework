<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;

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
        }, $entityManager->getRepository('UVDeskCoreFrameworkBundle:EmailTemplates')->findAll());

        return $emailTemplateCollection;
    }

    public static function applyAction(ContainerInterface $container, $entity, $value = null, $thread = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        switch (true) {
            case $entity instanceof CoreEntities\Ticket:
                $currentThread = isset($entity->currentThread) ? $entity->currentThread : '';
                $createdThread = isset($entity->createdThread) ? $entity->createdThread : '';
                
                $emailTemplate = $entityManager->getRepository('UVDeskCoreFrameworkBundle:EmailTemplates')->findOneById($value);

                if (empty($emailTemplate)) {
                    break;
                }

                // Only process attachments if required in the message body
                // @TODO: Revist -> Maybe we should always include attachments if they are provided??
                $attachments = [];
                if (!empty($createdThread) && (strpos($emailTemplate->getMessage(), '{%ticket.attachments%}') !== false || strpos($emailTemplate->getMessage(), '{% ticket.attachments %}') !== false)) {
                    $attachments = array_map(function($attachment) use ($container) { 
                        return str_replace('//', '/', $container->get('kernel')->getProjectDir() . "/public" . $attachment->getPath());
                    }, $entityManager->getRepository('UVDeskCoreFrameworkBundle:Attachment')->findByThread($createdThread));
                }

                $ticketPlaceholders = $container->get('email.service')->getTicketPlaceholderValues($entity);
                $subject = $container->get('email.service')->processEmailSubject($emailTemplate->getSubject(), $ticketPlaceholders);
                $message = $container->get('email.service')->processEmailContent($emailTemplate->getMessage(), $ticketPlaceholders);
                
                $thread = ($thread != null) ? $thread : $createdThread;
                if (!empty($thread)) {
                    $headers = ['References' => $entity->getReferenceIds()];
                
                    if (!empty($currentThread) && null != $currentThread->getMessageId()) {
                        $headers['In-Reply-To'] = $currentThread->getMessageId();
                    }

                    $messageId = $container->get('email.service')->sendMail($subject, $message, $entity->getCustomer()->getEmail(), $headers, $entity->getMailboxEmail(), $attachments ?? []);

                    // if (!empty($messageId)) {
                    //     $createdThread->setMessageId($messageId);
                    //     $entityManager->persist($createdThread);
                    //     $entityManager->flush();
                    // }

                    if($thread->getCc() || $thread->getBcc()) {
                        self::sendCcBccMail($container, $entity, $thread, $subject, $attachments);
                    }
                    
                } else {
                    $message = $container->get('email.service')->sendMail($subject, $message, $entity->getCustomer()->getEmail());
                }
                break;
            default:
                break;
        }
    }

    public static function sendCcBccMail($container, $entity, $thread, $subject, $attachments)
    {
        $message = '<html><body style="background-image: none"><p>Hello</p><br/><p>'.$thread->getMessage().'</p></body></html>';

        $messageId = $container->get('email.service')->sendMail($subject, $message, null, [], $entity->getMailboxEmail(), $attachments ?? [], $thread->getCc() ?: [], $thread->getBcc() ?: []);
    }
}

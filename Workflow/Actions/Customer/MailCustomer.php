<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Customer;

use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\EmailTemplates;
use Webkul\UVDesk\AutomationBundle\Workflow\Event;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\CustomerActivity;

class MailCustomer extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.customer.mail_customer';
    }

    public static function getDescription()
    {
        return "Mail to customer";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::CUSTOMER;
    }
    
    public static function getOptions(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        return array_map(function ($emailTemplate) {
            return [
                'id' => $emailTemplate->getId(),
                'name' => $emailTemplate->getName(),
            ];
        }, $entityManager->getRepository(EmailTemplates::class)->findAll());
    }

    public static function applyAction(ContainerInterface $container, Event $event, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        if (!$event instanceof CustomerActivity) {
            return;
        } else {
            $user = $event->getUser();
            $emailTemplate = $entityManager->getRepository(EmailTemplates::class)->findOneById($value);
    
            if (empty($user) || empty($emailTemplate)) {
                // @TODO: Send default email template
                return;
            }
        }

        $emailPlaceholders = $container->get('email.service')->getEmailPlaceholderValues($user, 'customer');
        $subject = $container->get('email.service')->processEmailSubject($emailTemplate->getSubject(), $emailPlaceholders);
        $message = $container->get('email.service')->processEmailContent($emailTemplate->getMessage(), $emailPlaceholders);
        
        $messageId = $container->get('email.service')->sendMail($subject, $message, $user->getEmail());
    }
}

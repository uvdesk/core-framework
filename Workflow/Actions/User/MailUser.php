<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\User;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\EmailTemplates;
use Webkul\UVDesk\AutomationBundle\Workflow\Event;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\UserActivity;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\CustomerActivity;

class MailUser extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.user.mail_user';
    }

    public static function getDescription()
    {
        return "Mail to User";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::USER;
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
        if (!$event instanceof UserActivity) {
            return;
        }
        
        $user = $event->getUser();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $emailTemplate = $entityManager->getRepository(EmailTemplates::class)->findOneById($value);

        if (empty($user) || empty($emailTemplate)) {
            // @TODO: Send default email template
            return;
        }

        $emailPlaceholders = $container->get('email.service')->getEmailPlaceholderValues($user);
        $subject = $container->get('email.service')->processEmailSubject($emailTemplate->getSubject(), $emailPlaceholders);
        $message = $container->get('email.service')->processEmailContent($emailTemplate->getMessage(), $emailPlaceholders);
        
        $messageId = $container->get('email.service')->sendMail($subject, $message, $user->getEmail());
    }
}

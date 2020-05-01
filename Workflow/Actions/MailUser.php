<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;

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
        }, $entityManager->getRepository('UVDeskCoreFrameworkBundle:EmailTemplates')->findAll());
    }

    public static function applyAction(ContainerInterface $container, $entity, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        switch (true) {
            case $entity instanceof CoreEntities\User:
                $emailTemplate = $entityManager->getRepository('UVDeskCoreFrameworkBundle:EmailTemplates')->findOneById($value);

                if (empty($emailTemplate)) {
                    // @TODO: Send default email template
                    return;
                }

                $emailPlaceholders = $container->get('email.service')->getEmailPlaceholderValues($entity);
                $subject = $container->get('email.service')->processEmailSubject($emailTemplate->getSubject(), $emailPlaceholders);
                $message = $container->get('email.service')->processEmailContent($emailTemplate->getMessage(), $emailPlaceholders);
                
                $messageId = $container->get('email.service')->sendMail($subject, $message, $entity->getEmail());
                break;
            default:
                break;
        }
    }
}

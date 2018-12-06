<?php

namespace Webkul\UVDesk\CoreBundle\Workflow\Actions\Customer;

use Webkul\UVDesk\CoreBundle\Entity as CoreEntities;
use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Webkul\UVDesk\CoreBundle\Entity\Ticket;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;

class MailCustomer extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.customer.mail_customer';
    }

    public static function getDescription()
    {
        return 'Mail to customer';
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
        }, $entityManager->getRepository('UVDeskCoreBundle:EmailTemplates')->findAll());
    }

    public static function applyAction(ContainerInterface $container, $entity, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        switch (true) {
            // Customer created
            case $entity instanceof CoreEntities\User:
                $emailTemplate = $entityManager->getRepository('UVDeskCoreBundle:EmailTemplates')->findOneById($value);

                if (empty($emailTemplate)) {
                    // @TODO: Send default email template
                    return;
                }

                $emailPlaceholders = $container->get('email.service')->getEmailPlaceholderValues($entity, 'customer');
                $subject = $container->get('email.service')->processEmailSubject($emailTemplate->getSubject(), $emailPlaceholders);
                $message = $container->get('email.service')->processEmailContent($emailTemplate->getMessage(), $emailPlaceholders);
                
                $messageId = $container->get('uvdesk.core.mailbox')->sendMail($subject, $message, $entity->getEmail());
                break;
            default:
                break;
        }
    }
}

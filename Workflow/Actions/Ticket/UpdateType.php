<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;

class UpdateType extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.update_type';
    }

    public static function getDescription()
    {
        return 'Set Type As';
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }

    public static function getOptions(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        return array_map(function ($ticketType) {
            return [
                'id' => $ticketType->getId(),
                'name' => $ticketType->getDescription(),
            ];
        }, $entityManager->getRepository('UVDeskCoreFrameworkBundle:TicketType')->findAll());
    }

    public static function applyAction(ContainerInterface $container, $entity, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        if($entity instanceof Ticket && $value) {
            $type = $entityManager->getRepository('UVDeskCoreFrameworkBundle:TicketType')->find($value);
            if($type) {
                $entity->setType($type);
                $entityManager->persist($entity);
                $entityManager->flush();
            } else {
                // Ticket Type Not Found. Disable Workflow/Prepared Response
                // $this->disableEvent($event, $entity);
            }
        }
    }
}

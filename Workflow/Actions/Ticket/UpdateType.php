<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\TicketType;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\AutomationBundle\Workflow\Event;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class UpdateType extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.update_type';
    }

    public static function getDescription()
    {
        return "Set Type As";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }

    public static function getOptions(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $collection = $entityManager->getRepository(TicketType::class)->findBy(['isActive' => true], ['code' => 'ASC']);

        return array_map(function ($ticketType) {
            return [
                'id' => $ticketType->getId(),
                'name' => $ticketType->getCode(),
            ];
        }, $collection);
    }

    public static function applyAction(ContainerInterface $container, Event $event, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        if (!$event instanceof TicketActivity) {
            return;
        } else {
            $ticket = $event->getTicket();
            $type = $entityManager->getRepository(TicketType::class)->find($value);
            
            if (empty($ticket) || empty($type)) {
                return;
            }
        }
        
        $ticket
            ->setType($type)
        ;

        $entityManager->persist($ticket);
        $entityManager->flush();
    }
}

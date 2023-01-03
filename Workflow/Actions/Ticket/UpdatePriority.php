<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\TicketPriority;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\AutomationBundle\Workflow\Event;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class UpdatePriority extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.update_priority';
    }

    public static function getDescription()
    {
        return "Set Priority As";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }

    public static function getOptions(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        return array_map(function ($ticketPriority) {
            return [
                'id' => $ticketPriority->getId(),
                'name' => $ticketPriority->getDescription(),
            ];
        }, $entityManager->getRepository(TicketPriority::class)->findAll());
    }

    public static function applyAction(ContainerInterface $container, Event $event, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        if (!$event instanceof TicketActivity) {
            return;
        } else {
            $ticket = $event->getTicket();
            $priority = $entityManager->getRepository(TicketPriority::class)->find($value);
            
            if (empty($ticket) || empty($priority)) {
                return;
            }
        }

        $ticket
            ->setPriority($priority)
        ;

        $entityManager->persist($ticket);
        $entityManager->flush();
    }
}

<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportLabel;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\AutomationBundle\Workflow\Event;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class UpdateLabel extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.update_label';
    }

    public static function getDescription()
    {
        return "Set Label As";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }

    public static function getOptions(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        return array_map(function ($label) {
            return [
                'id' => $label->getId(),
                'name' => $label->getName(),
            ];
        }, $container->get('ticket.service')->getUserLabels());
    }

    public static function applyAction(ContainerInterface $container, Event $event, $value = null)
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
        
        $isAlreadyAdded = 0;
        $labels = $container->get('ticket.service')->getTicketLabelsAll($ticket->getId());

        if (is_array($labels)) {
            foreach ($labels as $label) {
                if ($label['id'] == $value) {
                    $isAlreadyAdded = 1;
                }
            }
        }

        if (!$isAlreadyAdded) {
            $label = $entityManager->getRepository(SupportLabel::class)->find($value);

            if ($label) {
                $ticket
                    ->addSupportLabel($label)
                ;

                $entityManager->persist($ticket);
                $entityManager->flush();
            }
        }
    }
}

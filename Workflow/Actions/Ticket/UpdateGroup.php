<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportGroup;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\AutomationBundle\Workflow\Event;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class UpdateGroup extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.assign_group';
    }

    public static function getDescription()
    {
        return "Assign to group";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }

    public static function getOptions(ContainerInterface $container)
    {
        return $container->get('user.service')->getSupportGroups();
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
        
        $group = $entityManager->getRepository(SupportGroup::class)->find($value);

        if ($group) {
            $ticket
                ->setSupportGroup($group)
            ;
            
            $entityManager->persist($ticket);
            $entityManager->flush();
        } else {
            // User Group Not Found. Disable Workflow/Prepared Response
           // $this->disableEvent($event, $entity);
        }
    }
}

<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\AutomationBundle\Workflow\Event;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class UpdateAgent extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.assign_agent';
    }

    public static function getDescription()
    {
        return "Assign to agent";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }

    public static function getOptions(ContainerInterface $container)
    {
        $agentCollection = array_map(function ($agent) {
            return [
                'id' => $agent['id'],
                'name' => $agent['name'],
            ];
        }, $container->get('user.service')->getAgentPartialDataCollection());

        array_unshift($agentCollection, [
            'id' => 'responsePerforming',
            'name' => 'Response Performing Agent',
        ]);

        return $agentCollection;
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
        
        if ($value == 'responsePerforming' && is_object($currentUser = $container->get('security.token_storage')->getToken()?->getUser())) {
            if (null != $currentUser->getAgentInstance()) {
                $agent = $currentUser;
            }
        } else {
            $agent = $entityManager->getRepository(User::class)->find($value);

            if ($agent) {
                $agent = $entityManager->getRepository(User::class)->findOneBy(array('email' => $agent->getEmail()));
            }
        }

        if (!empty($agent)) {
            if ($entityManager->getRepository(User::class)->findOneById($agent->getId())) {
                $ticket
                    ->setAgent($agent)
                ;

                $entityManager->persist($ticket);
                $entityManager->flush();
            }
        } else {
            // Agent Not Found. Disable Workflow/Prepared Response
        }
    }
}

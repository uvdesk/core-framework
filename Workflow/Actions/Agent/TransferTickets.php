<?php

namespace Webkul\UVDesk\CoreBundle\Workflow\Actions\Agent;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreBundle\Entity\Ticket;
use Webkul\UVDesk\CoreBundle\Entity\User;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;

class TransferTickets extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.agent.transfer_tickets';
    }

    public static function getDescription()
    {
        return 'Transfer Tickets';
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::AGENT;
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

    public static function applyAction(ContainerInterface $container, $entity, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        if ($entity instanceof User) {
            if ($value == 'responsePerforming') {
                $user = $container->get('security.tokenstorage')->getToken()->getUser();
            } else {
                $user = $entityManager->getRepository('UVDeskCoreBundle:User')->find($value);
            }
            
            if (!empty($user) && $user != 'anon.') {
                $tickets = $entityManager->getRepository('UVDeskCoreBundle:Ticket')->getAgentTickets($entity->getId(), $container);

                foreach ($tickets as $ticket) {
                    $ticket->setAgent($user);
                    $entityManager->persist($ticket);
                    $entityManager->flush();
                }
            }
        }
    }
}

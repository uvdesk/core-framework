<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;

class RoundRobinTicketAssignment extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.round_robin_ticket_assignment';
    }

    public static function getDescription()
    {
        return "Round robin ticket assignment";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }

    public static function getOptions(ContainerInterface $container)
    {
        return [];
    }

    public static function applyAction(ContainerInterface $container, $entity, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        if ($entity instanceof Ticket ) {
            $agents = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User')->getAllAgents(null, $container);
            $agentCount =  count($agents['users']);
            foreach ($agents['users'] as $key => $agent) {
                $tickets = $entityManager->getRepository('UVDeskCoreFrameworkBundle:Ticket')->findBy(['agent' => $agent['id'], 'status' => 1]);

                $final[$agent['id']] = count($tickets);
            }

            $minAssignedAgent = array_keys($final, min($final));

            if (count($minAssignedAgent) == $agentCount ) {
                $minAssignedAgent = array_shift($minAssignedAgent);
            } else {
                $minAssignedAgent = min($minAssignedAgent);
            }

            if ($agent = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User')->findOneBy(array('id' => $minAssignedAgent))) {
                $entity->setAgent($agent);
                $entityManager->persist($entity);
                $entityManager->flush();
            }
        }
    }
}
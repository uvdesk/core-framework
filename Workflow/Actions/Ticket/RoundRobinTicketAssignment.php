<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;
use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;

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
        $final = [];
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $ticketEntity = $entity->getTicket();

        if ($entity instanceof CoreWorkflowEvents\Ticket\Create &&  $ticketEntity) {
            $agents = $entityManager->getRepository(User::class)->getAllAgents(null, $container);
            $agentCount =  count($agents['users']);

            foreach ($agents['users'] as $key => $agent) {
                $tickets = $entityManager->getRepository(Ticket::class)->findBy(['agent' => $agent['id'], 'status' => 1]);
                $final[$agent['id']] = count($tickets);
            }

            $minAssignedAgent = array_keys($final, min($final));

            if (count($minAssignedAgent) == $agentCount ) {
                $minAssignedAgent = array_shift($minAssignedAgent);
            } else {
                $minAssignedAgent = min($minAssignedAgent);
            }

            if ($agent = $entityManager->getRepository(User::class)->findOneBy(array('id' => $minAssignedAgent))) {
                $ticketEntity->setAgent($agent);
                $entityManager->persist($ticketEntity);
                $entityManager->flush();
            }
        }
    }
}
<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\PreparedResponse\Actions\Ticket;

use Webkul\UVDesk\AutomationBundle\PreparedResponse\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\AutomationBundle\PreparedResponse\Action as PreparedResponseAction;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;

class UpdateAgent extends PreparedResponseAction
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
                'id'   => $agent['id'],
                'name' => $agent['name'],
            ];
        }, $container->get('user.service')->getAgentPartialDataCollection());

        array_unshift($agentCollection, [
            'id'   => 'responsePerforming',
            'name' => 'Response Performing Agent',
        ]);

        return $agentCollection;
    }

    public static function applyAction(ContainerInterface $container, $entity, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        if ($entity instanceof Ticket) {
            if ($value == 'responsePerforming' && is_object($currentUser = $container->get('security.token_storage')->getToken()->getUser())) {
                $agent = $currentUser;
            } else {
                $agent = $entityManager->getRepository(User::class)->find($value);
                if ($agent) {
                    $agent = $entityManager->getRepository(User::class)->findOneBy(array('email' => $agent->getEmail()));
                }
            }
            if ($agent) {
                if($entityManager->getRepository(User::class)->findOneBy(array('id' => $agent->getId()))) {
                    $entity->setAgent($agent);
                    $entityManager->persist($entity);
                    $entityManager->flush();
                }
            } else {
                // Agent Not Found. Disable Workflow/Prepared Response
            }
        }
    }
}

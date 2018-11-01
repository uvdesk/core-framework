<?php

namespace Webkul\UVDesk\CoreBundle\Workflow\Actions\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;

class MailTeam extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.ticket.mail_team';
    }

    public static function getDescription()
    {
        return 'Mail to team';
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }

    public static function getOptions(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $emailTemplateCollection = array_map(function ($emailTemplate) {
            return [
                'id' => $emailTemplate->getId(),
                'name' => $emailTemplate->getName(),
            ];
        }, $entityManager->getRepository('UVDeskCoreBundle:EmailTemplates')->findAll());

        $supportTeamCollection = array_map(function ($supportTeam) {
            return [
                'id' => $supportTeam['id'],
                'name' => $supportTeam['name'],
            ];
        }, $container->get('user.service')->getSupportTeams());

        array_unshift($supportTeamCollection, [
            'id' => 'assignedTeam',
            'name' => 'Assigned Team',
        ]);

        return [
            'partResults' => $supportTeamCollection,
            'templates' => $emailTemplateCollection,
        ];
    }

    public static function applyAction(ContainerInterface $container, $entity, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
    }
}

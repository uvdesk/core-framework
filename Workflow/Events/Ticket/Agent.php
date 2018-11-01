<?php

namespace Webkul\UVDesk\CoreBundle\Workflow\Events\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\AutomationBundle\Workflow\Event as WorkflowEvent;

class Agent extends WorkflowEvent
{
    public static function getId()
    {
        return 'uvdesk.ticket.agent_updated';
    }

    public static function getDescription()
    {
        return 'Ticket Created';
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }
}

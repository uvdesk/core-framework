<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class Agent extends TicketActivity
{
    public static function getId()
    {
        return 'uvdesk.ticket.agent_updated';
    }

    public static function getDescription()
    {
        return "Agent Updated";
    }
}

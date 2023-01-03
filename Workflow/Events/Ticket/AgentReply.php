<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class AgentReply extends TicketActivity
{
    public static function getId()
    {
        return 'uvdesk.ticket.agent_reply';
    }

    public static function getDescription()
    {
        return "Agent Reply";
    }
}

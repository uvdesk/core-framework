<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class Team extends TicketActivity
{
    public static function getId()
    {
        return 'uvdesk.ticket.team_updated';
    }

    public static function getDescription()
    {
        return 'Team Updated';
    }
}

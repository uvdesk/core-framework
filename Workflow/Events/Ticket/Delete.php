<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class Delete extends TicketActivity
{
    public static function getId()
    {
        return 'uvdesk.ticket.removed';
    }

    public static function getDescription()
    {
        return "Ticket Deleted";
    }
}

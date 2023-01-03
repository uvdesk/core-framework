<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class Note extends TicketActivity
{
    public static function getId()
    {
        return 'uvdesk.ticket.note_added';
    }

    public static function getDescription()
    {
        return "Note Added";
    }
}

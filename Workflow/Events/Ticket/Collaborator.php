<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class Collaborator extends TicketActivity
{
    public static function getId()
    {
        return 'uvdesk.ticket.collaborator_updated';
    }

    public static function getDescription()
    {
        return "Collaborator Added";
    }
}

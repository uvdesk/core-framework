<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class CollaboratorReply extends TicketActivity
{
    public static function getId()
    {
        return 'uvdesk.ticket.collaborator_reply';
    }

    public static function getDescription()
    {
        return "Collaborator Reply";
    }
}

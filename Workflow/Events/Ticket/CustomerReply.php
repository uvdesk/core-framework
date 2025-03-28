<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Ticket;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\TicketActivity;

class CustomerReply extends TicketActivity
{
    public static function getId()
    {
        return 'uvdesk.ticket.customer_reply';
    }

    public static function getDescription()
    {
        return "Customer Reply";
    }
}

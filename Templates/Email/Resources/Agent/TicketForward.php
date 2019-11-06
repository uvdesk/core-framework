<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Templates\Email\Resources\Agent;

use Webkul\UVDesk\CoreFrameworkBundle\Templates\Email\UVDeskEmailTemplateInterface;

abstract class TicketForward implements UVDeskEmailTemplateInterface
{
    private static $type = "ticket";
    private static $name = 'Ticket Forwarded';
    private static $subject = 'Forwarded to you';
    private static $message = <<<MESSAGE
    <p></p>
    <p style="text-align: center;">{%global.companyLogo%}</p>
    <p style="text-align: center;">
        <br />
    </p>
    <p>Here go the ticket message:</p>
    <p>{%ticket.message%}
        <br />
    </p>
    <p>
        <br />
    </p>
    <p>Thanks and Regards</p>
    <p>{%global.companyName%}
        <br />
    </p>
    <p></p>
    <p>
        <br />
    </p>
    <p></p>
    <p>
        <br />
    </p>
    <p></p>
    <p></p>
    <p></p>
    <p></p>
MESSAGE;

    public static function getName()
    {
        return self::$name;
    }

    public static function getTemplateType()
    {
        return self::$type;
    }

    public static function getSubject()
    {
        return self::$subject;
    }

    public static function getMessage()
    {
        return self::$message;
    }
}
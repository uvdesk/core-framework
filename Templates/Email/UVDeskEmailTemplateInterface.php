<?php

namespace Webkul\UVDesk\CoreBundle\Templates\Email;

interface UVDeskEmailTemplateInterface
{
    public static function getName();
    public static function getSubject();
    public static function getMessage();
}
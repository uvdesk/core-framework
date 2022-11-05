<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Panel\Items\Settings;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\PanelSidebarItemInterface;
use Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Panel\Sidebars\Settings;

class Mailer implements PanelSidebarItemInterface
{
    public static function getTitle() : string
    {
        return "Mailer";
    }

    public static function getRouteName() : string
    {
        return 'helpdesk_member_mailer_settings';
    }

    public static function getSupportedRoutes() : array
    {
        return [
            'helpdesk_member_mailer_settings',
            'helpdesk_member_mailer_create_mailer_configuration',
            'helpdesk_member_mailer_update_mailer_configuration',
        ];
    }

    public static function getRoles() : array
    {
        return ['ROLE_ADMIN'];
    }

    public static function getSidebarReferenceId() : string
    {
        return Settings::class;
    }
}

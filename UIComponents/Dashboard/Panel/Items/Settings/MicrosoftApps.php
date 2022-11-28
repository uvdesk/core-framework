<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Panel\Items\Settings;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\PanelSidebarItemInterface;
use Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Panel\Sidebars\Settings;

class MicrosoftApps implements PanelSidebarItemInterface
{
    public static function getTitle() : string
    {
        return "Microsoft Apps";
    }

    public static function getRouteName() : string
    {
        return 'uvdesk_member_core_framework_microsoft_apps_settings';
    }

    public static function getSupportedRoutes() : array
    {
        return [
            'uvdesk_member_core_framework_microsoft_apps_settings', 
            'uvdesk_member_core_framework_microsoft_apps_settings_create_configuration', 
            'uvdesk_member_core_framework_microsoft_apps_settings_update_configuration', 
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

<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Homepage\Items;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\HomepageSectionItem;
use Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Homepage\Sections\Settings;

class SwiftMailer extends HomepageSectionItem
{
    CONST SVG = <<<SVG
<svg fill="#000000" height="60px" width="60px" version="1.1" id="Icons" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 32 32" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> 
    <path d="M31.5,8.9C30.7,7.2,29,6,27,6H15c-2,0-3.7,1.2-4.5,2.9L21,16.8L31.5,8.9z"></path> <path d="M21.6,18.8C21.4,18.9,21.2,19,21,19s-0.4-0.1-0.6-0.2l-8.5-6.4L10,11l0,0c0,0,0,0,0,0H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h5 c0.6,0,1,0.4,1,1v0c0,0.6-0.4,1-1,1H1c-0.6,0-1,0.4-1,1s0.4,1,1,1h8c0.6,0,1,0.4,1,1v0c0,0.6-0.4,1-1,1H4c-0.6,0-1,0.4-1,1 s0.4,1,1,1h6c0,2.8,2.2,5,5,5h12c2.8,0,5-2.2,5-5V11c0,0,0,0,0,0L21.6,18.8z">
    </path> </g> </g>
</svg>
SVG;

    public static function getIcon() : string
    {
        return self::SVG;
    }

    public static function getTitle() : string
    {
        return "Swift Mailer";
    }

    public static function getRouteName() : string
    {
        return 'helpdesk_member_swiftmailer_settings';
    }

    public static function getRoles() : array
    {
        return ['ROLE_ADMIN'];
    }

    public static function getSectionReferenceId() : string
    {
        return Settings::class;
    }
}

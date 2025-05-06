<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Search;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\SearchItemInterface;

class MicrosoftApps implements SearchItemInterface
{
    CONST SVG = <<<SVG
<svg fill="#000000" height="30px" width="30px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 47 47" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> 
    <path d="M45.707,0.248c-0.217-0.19-0.504-0.277-0.792-0.239l-20.096,2.69c-0.497,0.066-0.867,0.49-0.867,0.991v17.592 c0,0.552,0.448,1,1,1h20.096c0.552,0,1-0.448,1-1V1C46.048,0.711,45.924,0.437,45.707,0.248z M44.048,20.282H25.952V4.565 l18.096-2.422V20.282z"></path> <path d="M20.952,24.336c0,0-0.001,0-0.002,0L2.046,24.375c-0.552,0.001-0.998,0.449-0.998,1v15.001 c0,0.503,0.374,0.928,0.873,0.992l18.904,2.406c0.043,0.005,0.085,0.008,0.127,0.008c0.242,0,0.478-0.088,0.661-0.25 c0.215-0.19,0.339-0.463,0.339-0.75V25.336c0-0.266-0.105-0.52-0.294-0.708C21.471,24.441,21.216,24.336,20.952,24.336z M19.952,41.647L3.048,39.495V26.373l16.904-0.035V41.647z"></path> <path d="M20.824,3.187l-19,2.445C1.325,5.697,0.952,6.122,0.952,6.624v14.658c0,0.552,0.448,1,1,1h19c0.552,0,1-0.448,1-1V4.179 c0-0.288-0.124-0.561-0.339-0.751S21.108,3.15,20.824,3.187z M19.952,20.282h-17V7.504l17-2.188V20.282z"></path> <path d="M45.05,24.375l-20.096-0.028c0,0-0.001,0-0.001,0c-0.265,0-0.519,0.105-0.706,0.292c-0.188,0.188-0.293,0.442-0.293,0.708 v17.935c0,0.5,0.37,0.924,0.866,0.991l20.096,2.718C44.959,46.997,45.004,47,45.048,47c0.241,0,0.475-0.087,0.658-0.247 c0.217-0.19,0.342-0.464,0.342-0.753V25.375C46.048,24.823,45.601,24.376,45.05,24.375z M44.048,44.855l-18.096-2.447v-16.06 l18.096,0.025V44.855z">
    </path> </g> </g> </g>
</svg>
SVG;

    public static function getIcon() : string
    {
        return self::SVG;
    }

    public static function getTitle() : string
    {
        return "Microsoft Apps";
    }

    public static function getRouteName() : string
    {
        return 'uvdesk_member_core_framework_microsoft_apps_settings';
    }

    public static function getRoles() : array
    {
        return ['ROLE_ADMIN'];
    }

    public function getChildrenRoutes() : array
    {
        return [];
    }
}
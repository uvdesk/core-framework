<?php

namespace Webkul\UVDesk\CoreBundle\Package;

use Webkul\UVDesk\PackageManager\Extensions\HelpdeskExtension;
use Webkul\UVDesk\PackageManager\ExtensionOptions\HelpdeskExtension\Section as HelpdeskSection;

class UVDeskCoreConfiguration extends HelpdeskExtension
{
    const MAILBOX_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M30,33L6,18V12L30,27,54,12v6ZM5.9,5.992A5.589,5.589,0,0,0,1.745,7.817,5.882,5.882,0,0,0-.016,12.027v35.93a5.875,5.875,0,0,0,1.761,4.211A5.581,5.581,0,0,0,5.9,53.992H54.069a5.588,5.588,0,0,0,4.155-1.825A5.8,5.8,0,0,0,60,48V12a5.847,5.847,0,0,0-1.776-4.183,5.6,5.6,0,0,0-4.155-1.825H5.9Z" />
SVG;

    const GROUPS_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M54,36V52H45V36H42V24a3.807,3.807,0,0,1,4-4h7a3.807,3.807,0,0,1,4,4V36H54ZM49.5,18A4.5,4.5,0,1,1,54,13.5,4.487,4.487,0,0,1,49.5,18ZM33,52H27V39H20l6.37-16.081A4.224,4.224,0,0,1,30.379,20h0.253a4.244,4.244,0,0,1,4.009,2.922L40,39H33V52ZM30.49,18a4.5,4.5,0,1,1,4.5-4.5A4.487,4.487,0,0,1,30.49,18ZM15,52H6V36H3V24a3.807,3.807,0,0,1,4-4h7a3.807,3.807,0,0,1,4,4V36H15V52ZM10.5,18A4.5,4.5,0,1,1,15,13.5,4.487,4.487,0,0,1,10.5,18Z" />
SVG;

    const TEAMS_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M45,36V52H36V36H33V24a3.807,3.807,0,0,1,4-4h7a3.807,3.807,0,0,1,4,4V36H45ZM40.5,18A4.5,4.5,0,1,1,45,13.5,4.487,4.487,0,0,1,40.5,18ZM24,52H15V36H12V24a3.807,3.807,0,0,1,4-4h7a3.807,3.807,0,0,1,4,4V36H24V52ZM19.5,18A4.5,4.5,0,1,1,24,13.5,4.487,4.487,0,0,1,19.5,18Z" />
SVG;

    const AGENTS_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M30.5,2.974A22.308,22.308,0,0,0,8,25.081V42c0,4.078,2.85,8,7,8h8V29.994H13V25.081A17.337,17.337,0,0,1,30.5,7.887,17.337,17.337,0,0,1,48,25.081v4.913H38V50H48v2H31v5H46c4.15,0,7-3.278,7-7.355V25.081A22.308,22.308,0,0,0,30.5,2.974Z" />
SVG;

    const PRIVILEGES_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M44.985,21H43V16A13.239,13.239,0,0,0,30,3,13.24,13.24,0,0,0,17,16v5H14.989a5.087,5.087,0,0,0-5,5.143V51.857a5.087,5.087,0,0,0,5,5.143h30a5.087,5.087,0,0,0,5-5.143V26.143A5.087,5.087,0,0,0,44.985,21Zm-15,22.987a4.987,4.987,0,1,1,5-4.987A5.008,5.008,0,0,1,29.987,43.987ZM38,21H22V16a8,8,0,1,1,16,0v5Z" />
SVG;

    const CUSTOMERS_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M39.025,28a7,7,0,1,0-7.013-7A6.976,6.976,0,0,0,39.025,28ZM22.013,28A7,7,0,1,0,15,21,6.976,6.976,0,0,0,22.013,28Zm-0.751,4.29c-5.082,0-15.267,2.674-15.267,8V46H37V40C37,34.675,26.344,32.287,21.262,32.287Zm17.449,0c-0.633,0-1.352.046-2.116,0.114,2.53,1.92,4.4,4.216,4.4,7.6v6H53.978V40.287C53.978,34.961,43.793,32.287,38.711,32.287Z" />
SVG;

    const FOLDERS_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M25.216,11.023h-14.4a4.708,4.708,0,0,0-4.777,4.624L6.011,43.394a4.729,4.729,0,0,0,4.8,4.625H49.223a4.729,4.729,0,0,0,4.8-4.625L54,21a5.234,5.234,0,0,0-5-5H30Z" />
SVG;

    const CATEGORIES_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M6,18h6V12l-6,.014V18Zm10-6v6H54V12H16ZM6,28h6V22l-6,.014V28Zm10-6v6H54V22H16ZM6,38h6V32l-6,.014V38Zm10-6v6H54V32H16ZM6,48h6V42l-6,.014V48Zm10-6v6H54V42H16Z" />
SVG;

    const ARTICLES_BRICK_SVG = <<<SVG
    <path fill-rule="evenodd" d="M34.743,5.977h-19a4.769,4.769,0,0,0-4.726,4.8L11,49.19a4.77,4.77,0,0,0,4.726,4.8h28.52a4.79,4.79,0,0,0,4.749-4.8V20.381ZM32,23V9L46,23H32Z" />
SVG;

    const WORKFLOWS_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M25.783,21.527L10.245,6.019,6.016,10.248,21.524,25.756ZM37.512,6.019l6.119,6.119L6.016,49.783l4.229,4.229L47.89,16.4l6.119,6.119V6.019h-16.5ZM38.5,34.245l-4.229,4.229,9.389,9.389-6.149,6.149h16.5v-16.5L47.89,43.634Z" />
SVG;
    
    const PREPARED_RESPONSES_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M25.783,21.527L10.245,6.019,6.016,10.248,21.524,25.756ZM37.512,6.019l6.119,6.119L6.016,49.783l4.229,4.229L47.89,16.4l6.119,6.119V6.019h-16.5ZM38.5,34.245l-4.229,4.229,9.389,9.389-6.149,6.149h16.5v-16.5L47.89,43.634Z" />
SVG;
    
    const TICKET_TYPE_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M6,44v5H22V44H6ZM6,11v5H33V11H6ZM33,54V49H54V44H33V39H28V54h5ZM17,23v5H6v5H17v5h5V23H17ZM54,33V28H28v5H54ZM39,21h5V16H54V11H44V6H39V21Z" />
SVG;

    const SAVED_REPLIES_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M49.206,6.014H10.789a4.794,4.794,0,0,0-4.778,4.8L5.987,54,15,45H49c2.641,0,5.008-1.753,5.008-4.393V10.813A4.815,4.815,0,0,0,49.206,6.014ZM45,36H15V31H45v5Zm0-8H15V23H45v5Zm0-8H15V15H45v5Z" />
SVG;
    
    const TAG_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M42.935,14.247A4.683,4.683,0,0,0,39,12H11a5.182,5.182,0,0,0-5.015,5.313V43.74A5.164,5.164,0,0,0,11.036,49l27.782,0.026a4.972,4.972,0,0,0,4.117-2.22L53.972,30.526Z" />
SVG;

    const BRANDING_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M30,6a24,24,0,0,0,0,48,4,4,0,0,0,2.96-6.693,3.985,3.985,0,0,1,2.987-6.64h4.72A13.338,13.338,0,0,0,54,27.333C54,15.547,43.253,6,30,6ZM15.333,30a4,4,0,1,1,4-4A3.995,3.995,0,0,1,15.333,30Zm8-10.667a4,4,0,1,1,4-4A3.995,3.995,0,0,1,23.333,19.333Zm13.333,0a4,4,0,1,1,4-4A3.995,3.995,0,0,1,36.667,19.333Zm8,10.667a4,4,0,1,1,4-4A3.995,3.995,0,0,1,44.667,30Z"/>
SVG;

    const EMAIL_TEMPLATES_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M49.224,52.979H10.813a4.783,4.783,0,0,1-4.8-4.736V24.566a4.7,4.7,0,0,1,2.281-4.025l3.082-1.78,3.4,2.779-4.582,2.648,19.83,12.218,19.83-12.218-4.6-2.66,3.4-2.779,3.1,1.793A4.68,4.68,0,0,1,54,24.566l0.024,23.678A4.783,4.783,0,0,1,49.224,52.979ZM30.018,32.4L16,24V7H44V23.748L30.018,32V32.4ZM20,11h4v4H20V11Zm6,0h8v4H26V11Zm-6,7h4v4H20V18Zm6,0H40v4H26V18Z"/>
SVG;

    const BLOCK_SPAM_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M29.994,5.98A24.007,24.007,0,1,0,53.974,29.987,24,24,0,0,0,29.994,5.98ZM12,29.365A17.359,17.359,0,0,1,29.36,12a17.148,17.148,0,0,1,10.634,3.668L15.666,40A17.156,17.156,0,0,1,12,29.365ZM30.629,48a14.544,14.544,0,0,1-9.634-3.537L44.455,21a14.549,14.549,0,0,1,3.536,9.636A17.358,17.358,0,0,1,30.629,48Z" />
SVG;

    const DASHBOARD_ICON_SVG = <<<SVG
<path fill-rule="evenodd"  fill="rgb(158, 158, 158)" d="M8.000,18.000 L8.000,12.000 L12.000,12.000 L12.000,18.000 L17.000,18.000 L17.000,10.000 L20.000,10.000 L10.000,0.000 L-0.000,10.000 L3.000,10.000 L3.000,18.000 L8.000,18.000 Z" />
SVG;

    const TICKETS_ICON_SVG = <<<SVG
<path fill-rule="evenodd"  fill="rgb(158, 158, 158)" d="M19.000,4.000 L17.000,4.000 L16.995,12.998 L4.000,13.000 L4.000,15.000 C4.000,15.550 4.450,16.000 5.000,16.000 L16.000,16.000 L20.000,20.000 L20.000,5.000 C20.000,4.450 19.550,4.000 19.000,4.000 ZM15.000,10.000 L15.000,1.000 C15.000,0.450 14.550,0.000 14.000,0.000 L1.000,0.000 C0.450,0.000 -0.000,0.450 -0.000,1.000 L-0.000,15.000 L4.000,11.000 L14.000,11.000 C14.550,11.000 15.000,10.550 15.000,10.000 Z" />
SVG;

    const KNOWLEDGEBASE_ICON_SVG = <<<SVG
<path fill-rule="evenodd" fill="rgb(158, 158, 158)" d="M14.000,0.000 L2.000,0.000 C0.969,0.000 0.000,0.901 0.000,2.000 L0.000,18.000 C0.000,19.099 0.969,20.000 2.000,20.000 L14.000,20.000 C15.031,20.000 16.000,19.099 16.000,18.000 L16.000,2.000 C16.000,0.901 15.031,0.000 14.000,0.000 ZM3.000,3.000 L9.000,3.000 L9.000,11.000 L6.000,9.000 L3.000,11.000 L3.000,3.000 Z" />
SVG;

    public function loadDashboardItems()
    {
        return [
            HelpdeskSection::USERS => [
                [
                    'name' => 'Groups',
                    'route' => 'helpdesk_member_support_group_collection',
                    'brick_svg' => self::GROUPS_BRICK_SVG,
                    'permission'=>"ROLE_AGENT_MANAGE_GROUP"
                ],
                [
                    'name' => 'Teams',
                    'route' => 'helpdesk_member_support_team_collection',
                    'brick_svg' => self::TEAMS_BRICK_SVG,
                    'permission'=>"ROLE_AGENT_MANAGE_SUB_GROUP"
                    
                ],
                [
                    'name' => 'Agents',
                    'route' => 'helpdesk_member_account_collection',
                    'brick_svg' => self::AGENTS_BRICK_SVG,
                    'permission'=>'ROLE_AGENT_MANAGE_AGENT'
                ],
                [
                    'name' => 'Privileges',
                    'route' => 'helpdesk_member_privilege_collection',
                    'brick_svg' => self::PRIVILEGES_BRICK_SVG,
                    'permission'=>'ROLE_AGENT_MANAGE_AGENT_PRIVILEGE'
                ],
                [
                    'name' => 'Customers',
                    'route' => 'helpdesk_member_manage_customer_account_collection',
                    'brick_svg' => self::CUSTOMERS_BRICK_SVG,
                    'permission'=>'ROLE_AGENT_MANAGE_CUSTOMER'                   
                ],
            ],
            HelpdeskSection::AUTOMATION => [
                [
                    'name' => 'Ticket Types',
                    'route' => 'helpdesk_member_ticket_type_collection',
                    'brick_svg' => self::TICKET_TYPE_BRICK_SVG,
                    'permission'=>'ROLE_AGENT_MANAGE_TICKET_TYPE'                   
                    
                ],
                [
                    'name' => 'Tags',
                    'route' => 'helpdesk_member_ticket_tag_collection',
                    'brick_svg' => self::TAG_BRICK_SVG,
                    'permission'=>'ROLE_AGENT_MANAGE_TAG'
                ],
                [
                    'name' => 'Saved Replies',
                    'route' => 'helpdesk_member_saved_replies',
                    'brick_svg' => self::SAVED_REPLIES_BRICK_SVG,
                    'permission'=>'ROLE_AGENT_MANAGE_SAVED_REPLIES'
                ],
            ],
            HelpdeskSection::SETTINGS => [
                [
                    'name' => 'Branding',
                    'route' => 'helpdesk_member_helpdesk_theme',
                    'brick_svg' => self::BRANDING_BRICK_SVG,
                    'permission'=>'ROLE_ADMIN'
                ],
                [
                    'name' => 'Email Templates',
                    'route' => 'email_templates_action',
                    'brick_svg' => self::EMAIL_TEMPLATES_BRICK_SVG,
                    'permission'=>'ROLE_AGENT_MANAGE_EMAIL_TEMPLATE'
                    
                ],
                [
                    'name' => 'Swiftmailer',
                    'route' => 'helpdesk_member_swiftmailer_collection',
                    'brick_svg' => self::EMAIL_TEMPLATES_BRICK_SVG,
                    'permission'=>'ROLE_AGENT_MANAGE_SWIFTMAILER_TEMPLATE'
                ],
            ],
        ];
    }

    public function loadNavigationItems()
    {
        return [
            [
                'name' => 'Home',
                'route' => 'helpdesk_member_dashboard',
                'icon_svg' => self::DASHBOARD_ICON_SVG,
            ],
            [
                'name' => 'Tickets',
                'route' => 'helpdesk_member_ticket_collection',
                'icon_svg' => self::TICKETS_ICON_SVG,
            ],
        ];
    }
}

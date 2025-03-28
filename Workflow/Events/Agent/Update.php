<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Agent;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;

class Update extends AgentActivity
{
    public static function getId()
    {
        return 'uvdesk.agent.update';
    }

    public static function getDescription()
    {
        return "Agent Update";
    }
}

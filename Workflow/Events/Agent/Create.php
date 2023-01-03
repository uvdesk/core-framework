<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Agent;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;

class Create extends AgentActivity
{
    public static function getId()
    {
        return 'uvdesk.agent.created';
    }

    public static function getDescription()
    {
        return "Agent Created";
    }
}

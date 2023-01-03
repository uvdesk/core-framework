<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Agent;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;

class Delete extends AgentActivity
{
    public static function getId()
    {
        return 'uvdesk.agent.removed';
    }

    public static function getDescription()
    {
        return "Agent Deleted";
    }
}

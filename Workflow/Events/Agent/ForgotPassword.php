<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Agent;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\AgentActivity;

class ForgotPassword extends AgentActivity
{
    public static function getId()
    {
        return 'uvdesk.user.forgot_password';
    }

    public static function getDescription()
    {
        return "Agent Forgot Password";
    }
}

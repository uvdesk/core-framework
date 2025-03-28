<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\User;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\UserActivity;

class ForgotPassword extends UserActivity
{
    public static function getId()
    {
        return 'uvdesk.user.forgot_password';
    }

    public static function getDescription()
    {
        return "User Forgot Password";
    }
}

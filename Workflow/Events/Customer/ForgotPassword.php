<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Customer;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\CustomerActivity;

class ForgotPassword extends CustomerActivity
{
    public static function getId()
    {
        return 'uvdesk.user.forgot_password';
    }

    public static function getDescription()
    {
        return "Customer Forgot Password";
    }
}

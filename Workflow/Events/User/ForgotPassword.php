<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\User;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\AutomationBundle\Workflow\Event as WorkflowEvent;

class ForgotPassword extends WorkflowEvent
{
    public static function getId()
    {
        return 'uvdesk.user.forgot_password';
    }

    public static function getDescription()
    {
        return 'Forgot Password';
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::USER;
    }
}

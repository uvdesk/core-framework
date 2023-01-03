<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Customer;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\CustomerActivity;

class Create extends CustomerActivity
{
    public static function getId()
    {
        return 'uvdesk.customer.created';
    }

    public static function getDescription()
    {
        return 'Customer Created';
    }
}

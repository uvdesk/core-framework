<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Customer;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\CustomerActivity;

class Update extends CustomerActivity
{
    public static function getId()
    {
        return 'uvdesk.customer.updated';
    }

    public static function getDescription()
    {
        return "Customer Update";
    }
}

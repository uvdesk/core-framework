<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events\Customer;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\CustomerActivity;

class Delete extends CustomerActivity
{
    public static function getId()
    {
        return 'uvdesk.customer.removed';
    }

    public static function getDescription()
    {
        return 'Customer Deleted';
    }
}

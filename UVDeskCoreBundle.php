<?php

namespace Webkul\UVDesk\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Webkul\UVDesk\CoreBundle\DependencyInjection\CoreExtension;

class UVDeskCoreBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CoreExtension();
    }
}

<?php

namespace Webkul\UVDesk\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Webkul\UVDesk\CoreBundle\DependencyInjection\Compilers;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webkul\UVDesk\CoreBundle\DependencyInjection\CoreExtension;

class UVDeskCoreBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CoreExtension();
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compilers\EventListeners());
    }
}

<?php

namespace Webkul\UVDesk\CoreFrameworkBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Webkul\UVDesk\CoreFrameworkBundle\DependencyInjection\Compilers;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webkul\UVDesk\CoreFrameworkBundle\DependencyInjection\CoreExtension;

class CoreFrameworkBundle extends Bundle
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

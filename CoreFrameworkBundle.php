<?php

namespace Webkul\UVDesk\CoreFrameworkBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webkul\UVDesk\CoreFrameworkBundle\DependencyInjection\Passes;
use Webkul\UVDesk\CoreFrameworkBundle\DependencyInjection\CoreFramework;

class CoreFrameworkBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CoreFramework();
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new Passes\Events())
            ->addCompilerPass(new Passes\Routes())
            ->addCompilerPass(new Passes\Extendables())
            ->addCompilerPass(new Passes\Widgets())
            ->addCompilerPass(new Passes\DashboardComponents());
    }
}

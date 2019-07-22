<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\DependencyInjection\Passes;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Dashboard;
use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\NavigationInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\HomepageSectionInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\HomepageSectionItemInterface;

class DashboardComponents implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->has(Dashboard::class)) {
            $dashboardDefinition = $container->findDefinition(Dashboard::class);

            // Navigation Panel Items
            foreach ($container->findTaggedServiceIds(NavigationInterface::class) as $reference => $tags) {
                $dashboardDefinition->addMethodCall('appendNavigation', array(new Reference($reference)));
            }

            // Homepage Panel Sections
            foreach ($container->findTaggedServiceIds(HomepageSectionInterface::class) as $reference => $tags) {
                $dashboardDefinition->addMethodCall('appendHomepageSection', array(new Reference($reference)));
            }

            // Homepage Panel Section Items
            foreach ($container->findTaggedServiceIds(HomepageSectionItemInterface::class) as $reference => $tags) {
                $dashboardDefinition->addMethodCall('appendHomepageSectionItem', array(new Reference($reference)));
            }
        }
    }
}

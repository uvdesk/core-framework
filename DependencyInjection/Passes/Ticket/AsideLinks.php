<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\DependencyInjection\Passes\Ticket;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Tickets\AsideLinkInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Tickets\AsideLinkCollection;

class AsideLinks implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->has(AsideLinkCollection::class)) {
            $quickActionButtonCollectionDefinition = $container->findDefinition(AsideLinkCollection::class);

            foreach ($container->findTaggedServiceIds(AsideLinkInterface::class) as $id => $tags) {
                $quickActionButtonCollectionDefinition->addMethodCall('add', array(new Reference($id), $tags));
            }
        }
    }
}

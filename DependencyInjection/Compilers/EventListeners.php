<?php

namespace Webkul\UVDesk\CoreBundle\DependencyInjection\Compilers;

use Webkul\UVDesk\CoreBundle\EventDispatcher\Core;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Webkul\UVDesk\CoreBundle\EventListener\EventListenerInterface;

class EventListeners implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(Core::class)) {
            return;
        }

        $dispatcherDefinition = $container->findDefinition(Core::class);
        $taggedEventListeners = $container->findTaggedServiceIds('uvdesk.event_listener');
        
        foreach ($taggedEventListeners as $serviceId => $serviceTags) {
            $dispatcherDefinition->addMethodCall('registerTaggedService', array(new Reference($serviceId), $serviceTags));
        }
    }
}

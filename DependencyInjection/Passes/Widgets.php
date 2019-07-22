<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\DependencyInjection\Passes;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Webkul\UVDesk\CoreFrameworkBundle\Widgets\TicketWidget;
use Webkul\UVDesk\CoreFrameworkBundle\Widgets\TicketWidgetInterface;

class Widgets implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->has(TicketWidget::class)) {
            $ticketWidgetExtension = $container->findDefinition(TicketWidget::class);

            foreach ($container->findTaggedServiceIds(TicketWidgetInterface::class) as $id => $tags) {
                $ticketWidgetExtension->addMethodCall('addWidget', array(new Reference($id), $tags));
            }
        }
    }
}

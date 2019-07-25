<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\EventDispatcher;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webkul\UVDesk\CoreFrameworkBundle\EventListener\EventListenerInterface;

class Core extends EventDispatcher implements EventDispatcherInterface
{
    private $container;
    private $requestStack;

    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->requestStack = $requestStack;
    }

    public function registerTaggedService(EventListenerInterface $eventListener, array $tags = [])
    {
        foreach ($tags as $tag) {
            $this->addListener($tag['event'], [$eventListener, $tag['method']]);
        }
    }
}

<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\EventListener\Login;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\RouterInterface;

class LoginExceptionListener
{
    public function onKernelException(ExceptionEvent $event,RouterInterface $router)
    {
        $homepageRoute = $router->generate('helpdesk_member_handle_login', [], RouterInterface::ABSOLUTE_URL);
        $response = new RedirectResponse($homepageRoute);        
        $event->setResponse($response);
    }
}
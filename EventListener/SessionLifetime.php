<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

class SessionLifetime
{
    private $security;
    private $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        if (!$session->isStarted()) {
            return;
        }

        // Skip for login or other specific routes
        if (in_array($request->attributes->get('_route'), ['helpdesk_member_dashboard','helpdesk_customer_ticket_collection', 'helpdesk_customer_login', 'helpdesk_member_handle_login'])) {
            return;
        }

        if ($this->security->getUser() && !$session->has('_security_main')) {
            // Session has expired for an authenticated user
            $user = $this->security->getUser();
            if ($user && method_exists($user, 'getCurrentInstance')) {
                $userInstance = $user->getCurrentInstance();
                if ($userInstance) {
                    // $userInstance->setIsOnline(false);
                    $this->entityManager->flush();
                }
            }
        }
    }
}

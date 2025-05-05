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

    // On Kernal Request.
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        if (! $session->isStarted()) {
            return;
        }

        $now = time();
        $sessionLifetime = (int) ini_get('session.gc_maxlifetime');

        // Check if the session is expired by comparing timestamps
        if ($session->has('last_active_time')) {
            $lastActive = $session->get('last_active_time');
            if (($now - $lastActive) > $sessionLifetime) {
                // Session expired
                $user = $this->security->getUser();

                if ($user && method_exists($user, 'getCurrentInstance')) {
                    $userInstance = $user->getCurrentInstance();
                   
                    if ($userInstance) {
                        $userInstance->setIsOnline(false);

                        $this->entityManager->persist($userInstance);
                        $this->entityManager->flush();
                    }
                }

                return;
            }
        }

        // Update activity timestamp for next request
        $session->set('last_active_time', $now);
    }
}

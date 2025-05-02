<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\EventListener;

use Symfony\Component\Security\Http\Event\LogoutEvent;
use Doctrine\ORM\EntityManagerInterface;

class Logout
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onLogout(LogoutEvent $event)
    {
        $user = $event->getToken()->getUser();

        if ($user && method_exists($user, 'getCurrentInstance')) {
            $userInstance = $user->getCurrentInstance();
            if ($userInstance) {
                $userInstance->setIsOnline(false);
                $this->entityManager->flush();
            }
        }
    }
}

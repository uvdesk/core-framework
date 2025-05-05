<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Session extends AbstractController
{
    public function checkSession(SessionInterface $session): JsonResponse
    {
        if (! $this->getUser()) {
            return new JsonResponse(['session' => 'expired'], 401);
        }

        return new JsonResponse(['session' => 'active']);
    }
}
<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Yaml\Yaml;
use Webkul\UVDesk\CoreFrameworkBundle\Mailer\MailerService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;

class EmailSettings extends AbstractController
{
    public function loadSettings(UserService $userService, MailerService $mailerService)
    {
        if (!$userService->isAccessAuthorized('ROLE_ADMIN')) {
            throw new AccessDeniedException("Insufficient account privileges");
        }

        $mailerConfigurations = array_map(function ($configuartion) {
            return $configuartion->getId();
        }, $mailerService->parseMailerConfigurations());

        return $this->render('@UVDeskCoreFramework//Email//emailSettings.html.twig', [
            'mailers' => $mailerConfigurations,
            'email_settings' => [
                'id' => $this->getParameter('uvdesk.support_email.id'),
                'name' => $this->getParameter('uvdesk.support_email.name'),
                'mailer_id' => $this->getParameter('uvdesk.support_email.mailer_id')
            ],
        ]);
    }
}

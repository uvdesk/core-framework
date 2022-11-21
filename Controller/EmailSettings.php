<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Component\Yaml\Yaml;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Mailer\MailerService;

class EmailSettings extends AbstractController
{
    public function __construct(UserService $userService, TranslatorInterface $translator, MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
        $this->translator = $translator;
        $this->userService = $userService;
    }

    public function loadSettings()
    {
        if (!$this->userService->isAccessAuthorized('ROLE_ADMIN')) {
            throw new AccessDeniedException("Insufficient account privileges");
        }

        $swiftmailerConfigurations = array_map(function ($configuartion) {
            return $configuartion->getId();
        }, $this->mailerService->parseMailerConfigurations());

        return $this->render('@UVDeskCoreFramework//Email//emailSettings.html.twig', [
            'swiftmailers' => $swiftmailerConfigurations,
            'email_settings' => [
                'id' => $this->getParameter('uvdesk.support_email.id'),
                'name' => $this->getParameter('uvdesk.support_email.name'),
                'mailer_id' => $this->getParameter('uvdesk.support_email.mailer_id')
            ],
        ]);
    }
}

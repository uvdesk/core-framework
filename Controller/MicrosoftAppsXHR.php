<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\MailboxBundle\Utils\MailboxConfiguration;
use Webkul\UVDesk\MailboxBundle\Services\MailboxService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\MicrosoftIntegration;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\ORM\EntityManagerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\MicrosoftApp;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\MicrosoftAccount;

class MicrosoftAppsXHR extends AbstractController
{
    public function loadSettingsXHR(Request $request, EntityManagerInterface $entityManager, MicrosoftIntegration $microsoftIntegration)
    {
        $collection = array_map(function ($app) {
            return [
                'id' => $app->getId(),
                'name' => $app->getName(),
                'isEnabled' => $app->getIsEnabled(),
                // 'isDeleted' => $app->getIsVerified() ? $mailbox->getIsDeleted() : false,
                'isVerified' => $app->getIsVerified() ? $app->getIsVerified() : false,
            ];
        }, $entityManager->getRepository(MicrosoftApp::class)->findAll());

        return new JsonResponse($collection ?? []);
    }

    public function removeMailboxConfiguration($id, Request $request)
    {
        $mailboxService = $this->mailboxService;
        $existingMailboxConfiguration = $mailboxService->parseMailboxConfigurations();

        foreach ($existingMailboxConfiguration->getMailboxes() as $configuration) {
            if ($configuration->getId() == $id) {
                $mailbox = $configuration;

                break;
            }
        }

        if (empty($mailbox)) {
            return new JsonResponse([
                'alertClass' => 'danger',
                'alertMessage' => "No mailbox found with id '$id'.",
            ], 404);
        }

        $mailboxConfiguration = new MailboxConfiguration();

        foreach ($existingMailboxConfiguration->getMailboxes() as $configuration) {
            if ($configuration->getId() == $id) {
                continue;
            }

            $mailboxConfiguration->addMailbox($configuration);
        }

        file_put_contents($mailboxService->getPathToConfigurationFile(), (string) $mailboxConfiguration);

        return new JsonResponse([
            'alertClass' => 'success',
            'alertMessage' => $this->translator->trans('Mailbox configuration removed successfully.'),
        ]);
    }
}

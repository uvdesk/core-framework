<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller\Microsoft;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft\MicrosoftApp;
use Webkul\UVDesk\CoreFrameworkBundle\Services\MicrosoftIntegration;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;

class MicrosoftAppsXHR extends AbstractController
{
    public function loadSettingsXHR(Request $request, EntityManagerInterface $entityManager, MicrosoftIntegration $microsoftIntegration)
    {
        $collection = array_map(function ($app) {
            return [
                'id'         => $app->getId(),
                'name'       => $app->getName(),
                'isEnabled'  => $app->getIsEnabled(),
                'isVerified' => $app->getIsVerified() ? $app->getIsVerified() : false,
            ];
        }, $entityManager->getRepository(MicrosoftApp::class)->findAll());

        return new JsonResponse($collection ?? []);
    }

    public function toggleConfigurationStatus($id, Request $request, UserService $userService, EntityManagerInterface $entityManager, MicrosoftIntegration $microsoftIntegration, TranslatorInterface $translator)
    {
        $status = $request->query->get('status');

        if (empty($status) || !in_array($status, ['enable', 'disable'])) {
            return new JsonResponse([
                'alertClass'   => 'danger',
                'alertMessage' => $translator->trans("Unrecognized status of type '$status' provided."),
            ]);
        }

        $isEnabled = ($status == 'enable') ? true : false;

        $microsoftApp = $entityManager->getRepository(MicrosoftApp::class)->findOneById($id);

        if (empty($microsoftApp)) {
            return new JsonResponse([
                'alertClass'   => 'danger',
                'alertMessage' => $translator->trans("No microsoft app was found for the provided id '$id'."),
            ], 404);
        } else if ($microsoftApp->getIsEnabled() == $isEnabled) {
            return new JsonResponse([
                'alertClass'   => 'success',
                'alertMessage' => $translator->trans('No changes in app configuration details were found.'),
            ]);
        }

        $microsoftApp
            ->setIsEnabled($isEnabled)
        ;

        $entityManager->persist($microsoftApp);
        $entityManager->flush();

        return new JsonResponse([
            'alertClass'   => 'success',
            'alertMessage' => $translator->trans('Microsoft app has been updated successfully.'),
        ]);
    }

    public function removeConfiguration($id, Request $request, UserService $userService, EntityManagerInterface $entityManager, MicrosoftIntegration $microsoftIntegration, TranslatorInterface $translator)
    {
        $microsoftApp = $entityManager->getRepository(MicrosoftApp::class)->findOneById($id);

        if (empty($microsoftApp)) {
            return new JsonResponse([
                'alertClass'   => 'danger',
                'alertMessage' => $translator->trans("No microsoft app was found for the provided id '$id'."),
            ], 404);
        }

        $entityManager->remove($microsoftApp);
        $entityManager->flush();

        return new JsonResponse([
            'alertClass'   => 'success',
            'alertMessage' => $translator->trans('Microsoft app has been deleted successfully.'),
        ]);
    }
}

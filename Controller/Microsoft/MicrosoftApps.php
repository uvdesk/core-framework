<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller\Microsoft;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft\MicrosoftApp;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft\MicrosoftAccount;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\MicrosoftIntegration;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\Microsoft\Graph as MicrosoftGraph;

class MicrosoftApps extends AbstractController
{
    const DEFAULT_PERMISSIONS = [
        'offline_access', 'openid', 'profile', 'User.Read', 
        'IMAP.AccessAsUser.All', 'SMTP.Send', 'POP.AccessAsUser.All', 
        'Mail.Read', 'Mail.ReadBasic', 'Mail.Send', 'Mail.Send.Shared', 
    ];

    public function loadSettings(UserService $userService)
    {
        if (!$userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskCoreFramework//MicrosoftApps//listConfigurations.html.twig');
    }

    public function createConfiguration(Request $request, UserService $userService, EntityManagerInterface $entityManager, MicrosoftIntegration $microsoftIntegration)
    {
        if (!$userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $redirectEndpoint = str_replace('http://', 'https://', $this->generateUrl('uvdesk_member_core_framework_integrations_microsoft_apps_oauth_login', [], UrlGeneratorInterface::ABSOLUTE_URL));

        if ($request->getMethod() == 'POST') {
            $params = $request->request->all();

            $microsoftApp = $entityManager->getRepository(MicrosoftApp::class)->findOneByClientId($params['clientId']);

            if (empty($microsoftApp)) {
                $microsoftApp = new MicrosoftApp();
            }

            $microsoftApp
                ->setName($params['name'])
                ->setTenantId($params['tenantId'])
                ->setClientId($params['clientId'])
                ->setClientSecret($params['clientSecret'])
                ->setApiPermissions(self::DEFAULT_PERMISSIONS)
                ->setIsVerified(false)
                ->setIsEnabled(false)
            ;

            $entityManager->persist($microsoftApp);
            $entityManager->flush();

            return new RedirectResponse($microsoftIntegration->getAuthorizationUrl($microsoftApp, $redirectEndpoint, [
                'app' => $microsoftApp->getId()
            ]));
        }

        return $this->render('@UVDeskCoreFramework//MicrosoftApps//manageConfigurations.html.twig', [
            'microsoftApp'     => null, 
            'redirectEndpoint' => $redirectEndpoint,
        ]);
    }

    public function updateConfiguration($id, Request $request, UserService $userService, EntityManagerInterface $entityManager, MicrosoftIntegration $microsoftIntegration, TranslatorInterface $translator)
    {
        if (!$userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $microsoftApp = $entityManager->getRepository(MicrosoftApp::class)->findOneById($id);
        $redirectEndpoint = str_replace('http://', 'https://', $this->generateUrl('uvdesk_member_core_framework_integrations_microsoft_apps_oauth_login', [], UrlGeneratorInterface::ABSOLUTE_URL));

        if (empty($microsoftApp)) {
            $this->addFlash('warning', $translator->trans('No microsoft app was found for the provided details.'));

            return new RedirectResponse($this->generateUrl('uvdesk_member_core_framework_microsoft_apps_settings'));
        }

        if ($request->getMethod() == 'POST') {
            $params = $request->request->all();

            $microsoftApp
                ->setName($params['name'])
                ->setTenantId($params['tenantId'])
                ->setClientId($params['clientId'])
                ->setClientSecret($params['clientSecret'])
                ->setApiPermissions(self::DEFAULT_PERMISSIONS)
                ->setIsVerified(false)
                ->setIsEnabled(false)
            ;

            $entityManager->persist($microsoftApp);
            $entityManager->flush();

            return new RedirectResponse($microsoftIntegration->getAuthorizationUrl($microsoftApp, $redirectEndpoint, [
                'app' => $microsoftApp->getId()
            ]));
        }

        return $this->render('@UVDeskCoreFramework//MicrosoftApps//manageConfigurations.html.twig', [
            'microsoftApp'     => $microsoftApp, 
            'redirectEndpoint' => $redirectEndpoint,
        ]);
    }

    public function addMicrosoftAccount($appId, $origin, Request $request, EntityManagerInterface $entityManager, MicrosoftIntegration $microsoftIntegration, TranslatorInterface $translator)
    {
        $microsoftApp = $entityManager->getRepository(MicrosoftApp::class)->findOneById($appId);

        if (empty($microsoftApp)) {
            $this->addFlash('warning', $translator->trans('No microsoft app was found for the provided details.'));

            return new RedirectResponse($this->generateUrl($origin));
        }

        $redirectEndpoint = str_replace('http://', 'https://', $this->generateUrl('uvdesk_member_core_framework_integrations_microsoft_apps_oauth_login', [], UrlGeneratorInterface::ABSOLUTE_URL));

        return new RedirectResponse($microsoftIntegration->getAuthorizationUrl($microsoftApp, $redirectEndpoint, [
            'app'    => $microsoftApp->getId(), 
            'origin' => $origin, 
            'action' => 'add_account', 
        ]));
    }

    public function handleOAuthCallback(Request $request, MicrosoftIntegration $microsoftIntegration, TranslatorInterface $translator)
    {
        $params = $request->query->all();
        $entityManager = $this->getDoctrine()->getManager();

        if (empty($params['code']) || empty($params['state'])) {
            return new Response("Invalid request.", 404);
        }

        $state = ! empty($params['state']) ? json_decode($params['state'], true) : [];

        $microsoftApp = $entityManager->getRepository(MicrosoftApp::class)->findOneById($state['app']);
        $redirectEndpoint = str_replace('http://', 'https://', $this->generateUrl('uvdesk_member_core_framework_integrations_microsoft_apps_oauth_login', [], UrlGeneratorInterface::ABSOLUTE_URL));

        $accessTokenResponse = $microsoftIntegration->getAccessToken($microsoftApp, $params['code'], $redirectEndpoint);

        if (!empty($accessTokenResponse['access_token'])) {
            $microsoftApp
                ->setIsEnabled(true)
                ->setIsVerified(true)
            ;

            $accountDetails = MicrosoftGraph\Me::me($accessTokenResponse['access_token']);

            if (empty($accountDetails['displayName']) || empty($accountDetails['displayName'])) {
                $this->addFlash('warning', $translator->trans('Microsoft app settings were verifired successfully but was unable to retrieve user information. Please check your settings and try again later.'));
            } else {
                $account = $entityManager->getRepository(MicrosoftAccount::class)->findOneByEmail($accountDetails['mail']);
    
                if (empty($account)) {
                    $account = new MicrosoftAccount();
                }
    
                $account
                    ->setName($accountDetails['displayName'])
                    ->setEmail($accountDetails['mail'])
                    ->setCredentials(json_encode($accessTokenResponse))
                    ->setMicrosoftApp($microsoftApp)
                ;
    
                $entityManager->persist($microsoftApp);
                $entityManager->persist($account);
                $entityManager->flush();
    
                if (! empty($state['action']) && $state['action'] == 'add_account') {
                    $this->addFlash('success', $translator->trans('Microsoft account has been added successfully.'));
                } else {
                    $this->addFlash('success', $translator->trans('Microsoft app has been integrated successfully.'));
                }
            }
        } else {
            $this->addFlash('warning', $translator->trans('Microsoft app settings could not be verifired successfully. Please check your settings and try again later.'));
        }

        try {
            if (! empty($state['origin'])) {
                return new RedirectResponse($this->generateUrl($state['origin']));
            }
        } catch (\Exception $e) {
            // Invalid endpoint provided. Ignoring exception...
        }

        return new RedirectResponse($this->generateUrl('uvdesk_member_core_framework_microsoft_apps_settings'));
    }
}

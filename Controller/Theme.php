<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Website;

class Theme extends AbstractController
{
    private $translator;
    private $entityManager;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    public function updateHelpdeskTheme(Request $request)
    {
        $envPath = $this->getParameter('kernel.project_dir') . '/.env';
        $envContent = file_get_contents($envPath);
        $sessionExpiryInMinute = $envContent ? (preg_match('/UV_SESSION_COOKIE_LIFETIME=(\d+)/', $envContent, $matches) ? (int) $matches[1] : 60) : 60;
        $website = $this->entityManager->getRepository(Website::class)->findOneByCode('helpdesk');

        if ($request->getMethod() == "POST") {
            $params = $request->request->all();

            if (! filter_var($params['webhookUrl'], FILTER_VALIDATE_URL)) {
                $this->addFlash('danger', $this->translator->trans('warning ! Invalid webhook URL provided. Please enter a valid URL.'));

                return $this->render('@UVDeskCoreFramework/theme.html.twig', [
                    'website'              => $website,
                    'currentSessionExpiry' => $sessionExpiryInMinute / 60
                ]);
            }

            $website->setName($params['helpdeskName']);
            $website->setThemeColor($params['themeColor']);
            $website->setDisplayUserPresenceIndicator($params['displayUserPresenceIndicator']);
            $website->setWebhookUrl($params['webhookUrl']);

            $this->entityManager->persist($website);
            $this->entityManager->flush();

            if (! empty($params['website']['session_expiry'])) {
                $sessionExpiry = (int) $params['website']['session_expiry'];
                $sessionExpiryInSeconds = $sessionExpiry * 60; // Convert minutes to seconds
                $envContent = preg_replace('/^UV_SESSION_COOKIE_LIFETIME=\d+$/m', 'UV_SESSION_COOKIE_LIFETIME=' . $sessionExpiryInSeconds, $envContent);
                file_put_contents($envPath, $envContent);
            }

            $this->addFlash('success', $this->translator->trans('Success ! Helpdesk details saved successfully'));
        }

        return $this->render('@UVDeskCoreFramework/theme.html.twig', [
            'website'              => $website,
            'currentSessionExpiry' => $sessionExpiryInMinute / 60
        ]);
    }
}

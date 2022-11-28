<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\MicrosoftApp;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\MicrosoftAccount;
use Webkul\UVDesk\CoreFrameworkBundle\Mailer\Event\ConfigurationUpdatedEvent;
use Webkul\UVDesk\CoreFrameworkBundle\Mailer\MailerService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;

class Mailer extends AbstractController
{
    public function loadMailers(UserService $userService)
    {
        if (!$userService->isAccessAuthorized('ROLE_ADMIN')) {
            throw new AccessDeniedException("Insufficient account privileges");
        }

        return $this->render('@UVDeskCoreFramework//Mailer//listConfigurations.html.twig');
    }
    
    public function createMailerConfiguration(Request $request, EntityManagerInterface $entityManager, MailerService $mailerService, TranslatorInterface $translator)
    {
        $microsoftAppCollection = $entityManager->getRepository(MicrosoftApp::class)->findBy(['isEnabled' => true, 'isVerified' => true]);
        $microsoftAccountCollection = $entityManager->getRepository(MicrosoftAccount::class)->findAll();

        $microsoftAppCollection = array_map(function ($microsoftApp) {
            return [
                'id' => $microsoftApp->getId(), 
                'name' => $microsoftApp->getName(), 
            ];
        }, $microsoftAppCollection);

        $microsoftAccountCollection = array_map(function ($microsoftAccount) {
            return [
                'id' => $microsoftAccount->getId(), 
                'name' => $microsoftAccount->getName(), 
                'email' => $microsoftAccount->getEmail(), 
            ];
        }, $microsoftAccountCollection);

        if ($request->getMethod() == 'POST') {
            $params = $request->request->all();

            if (!empty($params['pass'])) {
                $params['pass'] = urlencode($params['pass']);
            }

            if ($params['transport'] == 'outlook_oauth') {
                $microsoftAccount = $entityManager->getRepository(MicrosoftAccount::class)->findOneById($params['user']);

                if (empty($microsoftAccount)) {
                    $this->addFlash('warning', 'No configuration details were found for the provided microsoft account.');

                    return $this->render('@UVDeskCoreFramework//Mailer//manageConfigurations.html.twig', [
                        'microsoftAppCollection' => $microsoftAppCollection, 
                        'microsoftAccountCollection' => $microsoftAccountCollection, 
                    ]);
                }

                $params['user'] = $microsoftAccount->getEmail();
                $params['client'] = $microsoftAccount->getMicrosoftApp()->getClientId();
            }

            $mailerConfiguration = $mailerService->createConfiguration($params['transport'], $params['id']);

            if (!empty($mailerConfiguration)) {
                $mailerConfiguration->initializeParams($params);
                $configurations = $mailerService->parseMailerConfigurations();
                
                $configurations[] = $mailerConfiguration;

                try {
                    $mailerService->writeMailerConfigurations($configurations);
                    $this->addFlash('success', $translator->trans('Mailer configuration created successfully.'));

                    return new RedirectResponse($this->generateUrl('helpdesk_member_mailer_settings'));
                } catch (\Exception $e) {
                    $this->addFlash('warning', $e->getMessage());
                }
            }
        }

        return $this->render('@UVDeskCoreFramework//Mailer//manageConfigurations.html.twig', [
            'microsoftAppCollection' => $microsoftAppCollection, 
            'microsoftAccountCollection' => $microsoftAccountCollection, 
        ]);
    }

    public function updateMailerConfiguration($id, Request $request, EntityManagerInterface $entityManager, ContainerInterface $container, MailerService $mailerService, TranslatorInterface $translator)
    {
        $mailerConfigurations = $mailerService->parseMailerConfigurations();
        
        foreach ($mailerConfigurations as $index => $configuration) {
            if ($configuration->getId() == $id) {
                $mailerConfiguration = $configuration;
                break;
            }
        }
       
        if (empty($mailerConfiguration)) {
            return new Response('', 404);
        }

        $microsoftAppCollection = $entityManager->getRepository(MicrosoftApp::class)->findBy(['isEnabled' => true, 'isVerified' => true]);
        $microsoftAccountCollection = $entityManager->getRepository(MicrosoftAccount::class)->findAll();

        $microsoftAppCollection = array_map(function ($microsoftApp) {
            return [
                'id' => $microsoftApp->getId(), 
                'name' => $microsoftApp->getName(), 
            ];
        }, $microsoftAppCollection);

        $microsoftAccountCollection = array_map(function ($microsoftAccount) {
            return [
                'id' => $microsoftAccount->getId(), 
                'name' => $microsoftAccount->getName(), 
                'email' => $microsoftAccount->getEmail(), 
            ];
        }, $microsoftAccountCollection);

        if ($request->getMethod() == 'POST') {
            $params = $request->request->all(); 
            $params['pass'] = urlencode($params['pass']);

            $existingMailerConfiguration = clone $mailerConfiguration;
            $mailerConfiguration = $mailerService->createConfiguration($params['transport'], $params['id']);

            $mailerConfiguration->initializeParams($params);
            
            // Dispatch mailer configuration updated event
            $event = new ConfigurationUpdatedEvent($mailerConfiguration, $existingMailerConfiguration);
            
            $container->get('uvdesk.core.event_dispatcher')->dispatch($event, ConfigurationUpdatedEvent::NAME);

            // Updated mailer configuration file
            $mailerConfigurations[$index] = $mailerConfiguration;
            
            $mailerService->writeMailerConfigurations($mailerConfigurations);
            $this->addFlash('success', $translator->trans('Mailer configuration updated successfully.'));

            return new RedirectResponse($this->generateUrl('helpdesk_member_mailer_settings'));
        }

        return $this->render('@UVDeskCoreFramework//Mailer//manageConfigurations.html.twig', [
            'configuration' => $mailerConfiguration->castArray(), 
            'microsoftAppCollection' => $microsoftAppCollection, 
            'microsoftAccountCollection' => $microsoftAccountCollection, 
        ]);
    }
}

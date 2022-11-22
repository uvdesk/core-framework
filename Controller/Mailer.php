<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
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
    
    public function createMailerConfiguration(Request $request, MailerService $mailer, TranslatorInterface $translator)
    {
        if ($request->getMethod() == 'POST') {
            $params = $request->request->all();
            $params['password'] = urlencode($params['password']);

            dump($params);

            $mailerConfiguration = $mailer->createConfiguration($params['transport'], $params['id']);

            dump($mailerConfiguration);
            
            if (!empty($mailerConfiguration)) {
                $mailerConfiguration->initializeParams($params);
                $configurations = $mailer->parseMailerConfigurations();
                
                dump($mailerConfiguration);
                die;

                $configurations[] = $mailerConfiguration;
                
                try {
                    $mailer->writeMailerConfigurations($configurations);
                    $this->addFlash('success', $translator->trans('Mailer configuration created successfully.'));
                    return new RedirectResponse($this->generateUrl('helpdesk_member_mailer_settings'));
                } catch (\Exception $e) {
                    $this->addFlash('warning', $e->getMessage());
                }
            }
        }

        return $this->render('@UVDeskCoreFramework//Mailer//manageConfigurations.html.twig');
    }

    public function updateMailerConfiguration($id, Request $request, ContainerInterface $container, MailerService $mailer, TranslatorInterface $translator)
    {
        $mailerConfigurations = $mailer->parseMailerConfigurations();
        
        foreach ($mailerConfigurations as $index => $configuration) {
            if ($configuration->getId() == $id) {
                $mailerConfiguration = $configuration;
                break;
            }
        }
       
        if (empty($mailerConfiguration)) {
            return new Response('', 404);
        }

        if ($request->getMethod() == 'POST') {
            $params = $request->request->all(); 
            $params['pass'] = base64_encode($params['pass']);

            dump($params);

            $existingMailerConfiguration = clone $mailerConfiguration;
            $mailerConfiguration = $mailer->createConfiguration($params['transport'], $params['id']);

            dump($existingMailerConfiguration, $mailerConfiguration);

            $mailerConfiguration->initializeParams($params);
            
            dump($mailerConfiguration);
              
            // // Dispatch mailer configuration updated event
            // $event = new ConfigurationUpdatedEvent($mailerConfiguration, $existingMailerConfiguration);
            
            // $container->get('uvdesk.core.event_dispatcher')->dispatch($event, ConfigurationUpdatedEvent::NAME);

            // Updated mailer configuration file
            $mailerConfigurations[$index] = $mailerConfiguration;
            
            $mailer->writeMailerConfigurations($mailerConfigurations);
            
            dump($mailerConfigurations);
            die;
            
            $this->addFlash('success', $translator->trans('Mailer configuration updated successfully.'));
            return new RedirectResponse($this->generateUrl('helpdesk_member_mailer_settings'));
        }

        return $this->render('@UVDeskCoreFramework//Mailer//manageConfigurations.html.twig', [
            'configuration' => $mailerConfiguration->castArray(),
        ]);
    }
}

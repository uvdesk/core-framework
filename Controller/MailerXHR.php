<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Mailer\Event\ConfigurationRemovedEvent;
use Webkul\UVDesk\CoreFrameworkBundle\Mailer\MailerService;

class MailerXHR extends AbstractController
{
    public function loadMailersXHR(Request $request, MailerService $mailerService)
    {
        if (true === $request->isXmlHttpRequest()) {
            $configurations = $mailerService->parseMailerConfigurations();

            $collection = array_map(function ($configuartion) {
                return [
                    'id' => $configuartion->getId(),
                    'email' => $configuartion->getUser(),
                    'transport' => $configuartion->getTransportName(),
                ];
            }, $configurations);

            return new JsonResponse($collection);
        } 

        return new JsonResponse([], 404);
    }

    public function removeMailerConfiguration(Request $request, ContainerInterface $container, MailerService $mailerService, TranslatorInterface $translator)
    {
        $params = $request->query->all();
        $configurations = $mailerService->parseMailerConfigurations();
       
        if (!empty($configurations)) {
            foreach ($configurations as $index => $configuration) {
                if ($configuration->getId() == $params['id']) {
                    $mailerConfiguration = $configuration;
                    break;
                }
            }

            if (!empty($mailerConfiguration)) {
                unset($configurations[$index]);

                // Dispatch mailer configuration removed event
                $event = new ConfigurationRemovedEvent($mailerConfiguration);
                $container->get('uvdesk.core.event_dispatcher')->dispatch($event,ConfigurationRemovedEvent::NAME);

                // Update mailer configuration file
                $mailerService->writeMailerConfigurations($configurations);
                
                return new JsonResponse([
                    'alertClass' => 'success',
                    'alertMessage' => $translator->trans('Mailer configuration removed successfully.'),
                ]);
            }
        }

        return new JsonResponse([
            'alertClass' => 'error',
            'alertMessage' => $translator->trans('No mailer configurations found for mailer id:') . $params['id'],
        ], 404);
    }
}

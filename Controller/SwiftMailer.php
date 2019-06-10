<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Webkul\UVDesk\CoreBundle\SwiftMailer\Event\ConfigurationUpdatedEvent;

class SwiftMailer extends Controller
{
    public function loadMailers()
    {
        return $this->render('@UVDeskCore//SwiftMailer//listConfigurations.html.twig');
    }

    public function createMailerConfiguration(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $params = $request->request->all();
            $container = null;

            $swiftmailer = $this->get('swiftmailer.service');

            $swiftmailerConfiguration = $swiftmailer->createConfiguration($params['transport'], $params['id']);
            if (!empty($swiftmailerConfiguration)) {
                $swiftmailerConfiguration->initializeParams($params);
                $configurations = $swiftmailer->parseSwiftMailerConfigurations();

                if (!empty($params['id'])) {
                    foreach ($configurations as $key => $value) {
                        if ($value->getId() == $params['id']) {
                            $container = $params['id'];
                        }
                    }
                }
                if (is_null($container)) {
                    $configurations[] = $swiftmailerConfiguration;
                    $swiftmailer->writeSwiftMailerConfigurations($configurations);

                    $this->addFlash('success', 'SwiftMailer configuration created successfully.');
                    return new RedirectResponse($this->generateUrl('helpdesk_member_swiftmailer_settings'));
                } else {
                    $this->addFlash('warning', "Error! Can't create multiple configuartion with same Swiftmailer ID.");
                    return new RedirectResponse($this->generateUrl('helpdesk_member_swiftmailer_settings'));
                }

            }
        }

        return $this->render('@UVDeskCore//SwiftMailer//manageConfigurations.html.twig', [
            'flag' => 'create',
        ]);
    }

    public function updateMailerConfiguration($id, Request $request)
    {
        $container = null;
        $swiftmailerConfiguration = null;
        $swiftmailer = $this->get('swiftmailer.service');
        $configurations = $swiftmailer->parseSwiftMailerConfigurations();

        foreach ($swiftmailerConfigurations as $index => $configuration) {
            if ($configuration->getId() == $id) {
                $swiftmailerConfiguration = $configuration;
                break;
            }
        }

        if ($request->getMethod() == 'POST') {
            $params = $request->request->all();

            foreach ($configurations as $index => $value) {
                if ($value->getId() == $params['id']) {
                    $container = $params['id'];
                }
            }

            if ($params['id'] == $id) {
                $this->updateSwiftMailer($swiftmailerConfiguration, $configuration, $params, $configurations, $index, $swiftmailer);
                return new RedirectResponse($this->generateUrl('helpdesk_member_swiftmailer_settings'));
            } elseif (is_null($container)) {
                $this->updateSwiftMailer($swiftmailerConfiguration, $configuration, $params, $configurations, $index, $swiftmailer);
            } else {
                $this->addFlash('warning', "Error! Can't update multiple configuartion with same Swiftmailer ID.");
                return new RedirectResponse($this->generateUrl('helpdesk_member_swiftmailer_settings'));
            }

        }

        return $this->render('@UVDeskCore//SwiftMailer//manageConfigurations.html.twig', [
            'configuration' => $swiftmailerConfiguration->castArray(), 'flag' => 'update',
        ]);
    }

    public function updateSwiftMailer($swiftmailerConfiguration, $configuration, $params, $configurations, $index, $swiftmailer)
    {
        $swiftmailerConfiguration->initializeParams($params, true);

        $configurations[$index] = $configuration;

        $swiftmailer->writeSwiftMailerConfigurations($configurations);

        // Dispatch swiftmailer configuration removed event

        $event = new ConfigurationUpdatedEvent($swiftmailerConfiguration);

        $this->get('uvdesk.core.event_dispatcher')->dispatch(ConfigurationUpdatedEvent::NAME, $event);

        $this->addFlash('success', 'SwiftMailer configuration updated successfully.');
        return;
    }
}

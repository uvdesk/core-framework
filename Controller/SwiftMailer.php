<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
            $swiftmailer = $this->get('swiftmailer.service');

            $swiftmailerConfiguration = $swiftmailer->createConfiguration($params['transport'], $params['id']);

            if (!empty($swiftmailerConfiguration)) {
                $swiftmailerConfiguration->initializeParams($params);
                $configurations = $swiftmailer->parseSwiftMailerConfigurations();

                $configurations[] = $swiftmailerConfiguration;
                $swiftmailer->writeSwiftMailerConfigurations($configurations);

                $this->addFlash('success', 'SwiftMailer configuration created successfully.');
                return new RedirectResponse($this->generateUrl('helpdesk_member_swiftmailer_settings'));
            }
        }

        return $this->render('@UVDeskCore//SwiftMailer//manageConfigurations.html.twig');
    }

    public function updateMailerConfiguration($id, Request $request)
    {
        $swiftmailerConfiguration = null;
        $swiftmailer = $this->get('swiftmailer.service');
        $configurations = $swiftmailer->parseSwiftMailerConfigurations();

        foreach ($configurations as $index => $configuration) {
            if ($configuration->getId() == $id) {
                $swiftmailerConfiguration = $configuration;
                break;
            }
        }
        
        if ($request->getMethod() == 'POST') {
            $params = $request->request->all();
            $swiftmailerConfiguration->initializeParams($params, true);

            $configurations[$index] = $configuration;

            $swiftmailer->writeSwiftMailerConfigurations($configurations);
            
            $this->addFlash('success', 'SwiftMailer configuration updated successfully.');
            return new RedirectResponse($this->generateUrl('helpdesk_member_swiftmailer_settings'));
        }

        return $this->render('@UVDeskCore//SwiftMailer//manageConfigurations.html.twig', [
            'configuration' => $swiftmailerConfiguration->castArray(),
        ]);
    }
}

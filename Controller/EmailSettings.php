<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Component\Yaml\Yaml;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EmailSettings extends Controller
{
    public function loadSettings()
    {
	//Disabling Agent's access when agent enters URL 
        if (!$this->get('user.service')->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $swiftmailerConfigurations = array_map(function ($configuartion) {
            return $configuartion->getId();
        }, $this->get('swiftmailer.service')->parseSwiftMailerConfigurations());

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

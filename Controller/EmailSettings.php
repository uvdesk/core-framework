<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\Yaml\Yaml;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EmailSettings extends Controller
{
    public function loadSettings()
    {
        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk.yaml';
        $file_content = file_get_contents($filePath);

        // Convert yaml file content into array and merge existing mailbox and new mailbox
        $file_content_array = Yaml::parse($file_content, 6);
        $result = $file_content_array['uvdesk']['support_email'];

        return $this->render('@UVDeskCore//Email//emailSettings.html.twig', [
            'email_settings' => $result,
            'swiftmailers' => $this->container->get('swiftmailer.service')->getSwiftmailerIds(),
        ]);
    }
}
<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EmailSettingsXHR extends Controller
{
    public function loadSettingsXHR()
    {
        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk.yaml';
        $file_content = file_get_contents($filePath);

        // Convert yaml file content into array and merge existing mailbox and new mailbox
        $file_content_array = Yaml::parse($file_content, 6);
        $result = $file_content_array['uvdesk']['support_email'];

        return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
    }

    public function updateSettingsXHR(Request $request)
    {
        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk.yaml';
        $supportEmailConfiguration = json_decode($request->getContent(), true);

        $file_content_array = strtr(require __DIR__ . "/../Templates/uvdesk.php", [
            '{{ SUPPORT_EMAIL_ID }}' => $supportEmailConfiguration['id'],
            '{{ SUPPORT_EMAIL_NAME }}' => $supportEmailConfiguration['name'],
            '{{ SUPPORT_EMAIL_MAILER_ID }}' => $supportEmailConfiguration['mailer_id'],
        ]);
        
        // update uvdesk.yaml file
        file_put_contents($filePath, $file_content_array);

        return new Response(json_encode($supportEmailConfiguration), 200, ['Content-Type' => 'application/json']);
    }
}
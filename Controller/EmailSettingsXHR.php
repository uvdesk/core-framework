<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EmailSettingsXHR extends Controller
{
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

        $result = [
            'alertClass' => "success",
            'email_settings' => [
                'id' => $supportEmailConfiguration['id'],
                'name' => $supportEmailConfiguration['name'],
                'mailer_id' => $supportEmailConfiguration['mailer_id'],
            ],
            'alertMessage' => "Success ! Email settings are updated successfully.",
        ];

        return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
    }
}
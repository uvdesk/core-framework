<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

class EmailSettingsXHR extends Controller
{
    public function updateSettingsXHR(Request $request)
    {
        $filePath = $this->get('kernel')->getProjectDir() . '/config/packages/uvdesk.yaml';
        $supportEmailConfiguration = json_decode($request->getContent(), true);
        $value = Yaml::parseFile($filePath);
        if (is_null($value['uvdesk']['support_email'])) {
            $file_content_array = strtr(require __DIR__ . "/../Templates/uvdesk.php", [
                '{{ SUPPORT_EMAIL_ID }}' => $supportEmailConfiguration['id'],
                '{{ SUPPORT_EMAIL_NAME }}' => $supportEmailConfiguration['name'],
                '{{ SUPPORT_EMAIL_MAILER_ID }}' => $supportEmailConfiguration['mailer_id'],
            ]);
        } else {
            $value['uvdesk']['support_email']['id'] = $supportEmailConfiguration['id'];
            $value['uvdesk']['support_email']['name'] = $supportEmailConfiguration['name'];
            $value['uvdesk']['support_email']['mailer_id'] = $supportEmailConfiguration['mailer_id'];

            $file_content_array = Yaml::dump($value);
        }

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

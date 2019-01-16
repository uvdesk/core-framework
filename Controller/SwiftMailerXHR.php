<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SwiftMailerXHR extends Controller
{
    public function loadSettingsXHR(Request $request)
    {
        if (true === $request->isXmlHttpRequest()) {
            $file_content_array = Yaml::parse(file_get_contents(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml'), 6);
            $listSwiftmailer = isset($file_content_array['swiftmailer']['mailers']) ? $file_content_array['swiftmailer']['mailers'] : '';
            return new Response(json_encode($listSwiftmailer), 200, ['Content-Type' => 'application/json']);
        } 
        return new Response(json_encode([]), 404);
    }

    public function removeMailer(Request $request)
    {
        $swiftmailerId = $request->query->get('id');
        $file_content_array = Yaml::parse(file_get_contents(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml'), 6);
        if (isset($file_content_array['swiftmailer']['mailers'])) {
            $swiftmailers = $file_content_array['swiftmailer']['mailers'];
            unset($swiftmailers[$swiftmailerId]);
            if (empty($swiftmailers))
                $swiftmailers = null;
            $file_content_array['swiftmailer']['mailers'] = $swiftmailers;
        }

        // Write the content with new swiftmailer details in file
        $updateFile = file_put_contents(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml', Yaml::dump($file_content_array, 6));
        
        if($updateFile) {
            $json['alertClass'] = 'success';
            $json['alertMessage'] = 'Success ! Swiftmailer removed successfully.';
        }else{
            $json['alertClass'] = 'error';
            $json['alertMessage'] = 'File not found';
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    private function checkExistingSwiftmailer($uniqueId = null, $email = null, $currentswiftmailer =null)
    {
        $isExist = false;
        $file_content_array = Yaml::parse(file_get_contents(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml'), 6);
        $existingSwiftmailer = $file_content_array['swiftmailer']['mailers'];
        
        if ($existingSwiftmailer) {
            foreach ($existingSwiftmailer as $index => $swiftmailerDetails) {
                if ($index == $uniqueId || $swiftmailerDetails['username'] == $email && $currentswiftmailer['username'] != $swiftmailerDetails['username']) {
                        $isExist = true;
                }
            }
        }

        return $isExist;
    }

    private function getSwiftmailerDetails($swiftmailerId)
    {
        $file_content_array = Yaml::parse(file_get_contents(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml'), 6);
        $swiftmailers = $file_content_array['swiftmailer']['mailers'];
        
        if ($swiftmailers && $swiftmailerId) {
            foreach ($swiftmailers as $index => $swiftmailerDetails) {
                if($index == $swiftmailerId) {
                    $swiftmailer['name'] = $swiftmailerId;
                    foreach($swiftmailerDetails as $details => $value) {
                        $swiftmailer[$details] = $value;
                    }
                }
            }
        }

        return $swiftmailer;
    }
}

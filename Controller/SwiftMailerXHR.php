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
            $file_content_array = $this->getYamlContentAsArray(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml');
            $listSwiftmailer = isset($file_content_array['swiftmailer']['mailers'])? $file_content_array['swiftmailer']['mailers']:'';
            return new Response(json_encode($listSwiftmailer), 200, ['Content-Type' => 'application/json']);
        } 
        return new Response(json_encode([]), 404);
    }
    
    public function removeMailer(Request $request)
    {
        $swiftmailerId = $request->query->get('id');
        $file_content_array = $this->getYamlContentAsArray(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml');
        if (isset($file_content_array['swiftmailer']['mailers'])) {
            $swiftmailers = $file_content_array['swiftmailer']['mailers'];
            unset($swiftmailers[$swiftmailerId]);
            if (empty($swiftmailers))
                $swiftmailers = null;
            $file_content_array['swiftmailer']['mailers'] = $swiftmailers;
        }
        // Final write the content with new swiftmailer details in file
        $updateFile = $this->setYamlContent(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml',$file_content_array);
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
 
    private function setYamlContent($filePath, $arrayContent)
    {
        // Write the content with new swiftmailer details in file
        return file_put_contents($filePath, Yaml::dump($arrayContent, 6));
    }

    private function getYamlContentAsArray($filePath)
    {
        // Fetch existing content in file
        $file_content = '';
        if ($fh = fopen($filePath, 'r')) {
            while (!feof($fh)) {
                $file_content = $file_content.fgets($fh);
            }
        }
        // Convert yaml file content into array and merge existing swiftmailer and new swiftmailer
        return Yaml::parse($file_content, 6);
    }

    private function checkExistingSwiftmailer($uniqueId = null, $email = null, $currentswiftmailer =null)
    {
        $isExist = false;
        $file_content_array = $this->getYamlContentAsArray(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml');
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

    private function getswiftmailerDetails($swiftmailerId)
    {
        $file_content_array = $this->getYamlContentAsArray(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml');
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

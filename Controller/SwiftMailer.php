<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SwiftMailer extends Controller
{
    public function loadSettings()
    {
        return $this->render('@UVDeskCore//Swiftmailer//settings.html.twig');
    }
    
    public function createMailer(Request $request)
    {
        $data = $request->request->all();
        $errors = [];

        if($request->getMethod() == 'POST') {
            $isExistSwiftmailer = $this->checkExistingSwiftmailer($data['name'], $data['username']);
            
            if(!$isExistSwiftmailer) {
                $filePath = dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml';
                // get file content and index
                $file = file($filePath);
               
                $newSwiftMailer[$data['name']] = [
                    'transport' => $data['transport'],
                    'username'  => $data["username"],
                    'password'  => $data["password"],
                ];

                $file_content_array = Yaml::parse(file_get_contents($filePath));
                
                if (isset($file_content_array['swiftmailer']) && isset($file_content_array['swiftmailer']['mailers'])) {
                    $existingSwiftmailerCount = sizeof($file_content_array['swiftmailer']['mailers']);
                    $file_content_array['swiftmailer']['mailers'] = array_merge($file_content_array['swiftmailer']['mailers'], $newSwiftMailer);
                } else {
                    $file_content_array['swiftmailer']['mailers'] = $newSwiftMailer;
                }

                // Write the content with new swiftmailer details in file
                $updateFile = file_put_contents($filePath, Yaml::dump($file_content_array, 6));

                $this->addFlash('success', 'Swift Mailer details added successfully.');
                return $this->redirectToRoute('helpdesk_member_swiftmailer_settings');

            } else {
                $this->addFlash('warning', 'Swift Mailer with same name or email already exist.');
            }
        }

        return $this->render('@UVDeskCore//Swiftmailer//createMailer.html.twig', array(
            'errors' => json_encode($errors)
        ));
    }

    public function updateMailer($swiftmailerId, Request $request)
    {
        $data = $request->request->all();
        $errors = [];
        $swiftmailerDetails = $this->getswiftmailerDetails($swiftmailerId);

        $filePath = dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml';
        $file = file($filePath);

        if($request->getMethod() == 'POST') {

            $isExistSwiftmailer = $this->checkExistingSwiftmailer($swiftmailerId, $data['username']);
            $isExistEmail = $this->checkExistingSwiftmailer(null, $data['username'], $swiftmailerDetails);
            if(!$isExistEmail){
                if($isExistSwiftmailer){

                    $file_content_array = Yaml::parse(file_get_contents($filePath));
                    $swiftmailers = $file_content_array['swiftmailer']['mailers'];
                    unset($swiftmailers[$swiftmailerId]);
                    if (empty($swiftmailers))
                        $swiftmailers = null;
                    $file_content_array['swiftmailer']['mailers'] = $swiftmailers;
                }
    
                $newSwiftMailer[$swiftmailerId] = [
                    'transport' => $data['transport'],
                    'username'  => $data["username"],
                    'password'  => (!empty($data["password"])) ? $data["password"] : $swiftmailerDetails['password'],
                ];
    
                $file_content_array = Yaml::parse(file_get_contents($filePath));
                
                if (isset($file_content_array['swiftmailer']) && $file_content_array['swiftmailer']['mailers']) {
                    $existingSwiftmailerCount = sizeof($file_content_array['swiftmailer']['mailers']);
                    $file_content_array['swiftmailer']['mailers'] = array_merge($file_content_array['swiftmailer']['mailers'], $newSwiftMailer);
                } else {
                    $file_content_array['swiftmailer']['mailers'] = $newSwiftMailer;
                }
                // Write the content with new swiftmailer details in file
                $updateFile = file_put_contents($filePath, Yaml::dump($file_content_array, 6));
    
                $this->addFlash('success', 'Swift Mailer details updated successfully.');
                return $this->redirectToRoute('helpdesk_member_swiftmailer_settings');
            } else {
                $this->addFlash('warning', 'Swift Mailer with same email already exist.');
            }
        }

        return $this->render('@UVDeskCore//Swiftmailer//updateMailer.html.twig', array(
            'errors' => json_encode($errors),
            'swiftmailerDetails' => $swiftmailerDetails
        ));

    }

    private function setYamlContent ($filePath, $arrayContent)
    {
        // Write the content with new swiftmailer details in file
        return file_put_contents($filePath, Yaml::dump($arrayContent, 6));
    }
  
    private function checkExistingSwiftmailer($uniqueId = null, $email = null, $currentswiftmailer =null)
    {
        $isExist = false;
        $file_content_array = Yaml::parse(file_get_contents(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml'), 6);
        $existingSwiftmailer = isset($file_content_array['swiftmailer']['mailers'])?  $file_content_array['swiftmailer']['mailers'] : '';

        if ($existingSwiftmailer) {
            foreach ($existingSwiftmailer as $index => $swiftmailerDetails) {
                if ($index == $uniqueId || $swiftmailerDetails['username'] == $email && $currentswiftmailer['username'] != $swiftmailerDetails['username']) {
                    $isExist = true;
                }
            }
        }

        return $isExist;
    }

    private function getSwiftmailerDetails ($swiftmailerId)
    {

        $file_content_array = Yaml::parse(file_get_contents(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml'));
        $swiftmailers = $file_content_array['swiftmailer']['mailers'];

        if ($swiftmailers && $swiftmailerId) {
            foreach ($swiftmailers as $index => $swiftmailerDetails) {
                if ($index == $swiftmailerId) {
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

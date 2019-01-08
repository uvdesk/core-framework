<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

class Swiftmailer extends Controller
{
    public function collectionSwiftmailer()
    {
        return $this->render('@UVDeskCore//Swiftmailer//swiftmailerList.html.twig');
    }

    public function collectionSwiftmailerXHR(Request $request)
    {
        if (true === $request->isXmlHttpRequest()) {
            $file_content_array = $this->getYamlContentAsArray(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml');
            $listSwiftmailer = $file_content_array['swiftmailer']['mailers'];
            return new Response(json_encode($listSwiftmailer), 200, ['Content-Type' => 'application/json']);
        } 
        return new Response(json_encode([]), 404);
    }
    public function removeExistingSwiftmailer (Request $request)
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
        // Final write the content with new mailbox details in file
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
    // Adding Swiftmailer
    public function addSwiftmailer(Request $request)
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

                $file_content_array = $this->getYamlContentAsArray($filePath);
                
                if (isset($file_content_array['swiftmailer']) && $file_content_array['swiftmailer']['mailers']) {
                    $existingSwiftmailerCount = sizeof($file_content_array['swiftmailer']['mailers']);
                    $file_content_array['swiftmailer']['mailers'] = array_merge($file_content_array['swiftmailer']['mailers'], $newSwiftMailer);
                } else {
                    $file_content_array['swiftmailer']['mailboxes'] = $newSwiftMailer;
                }
                $updateFile = $this->setYamlContent($filePath, $file_content_array);
                $this->addFlash('success', 'Swifmailer detail added successfully.');

                return $this->redirectToRoute('helpdesk_member_swiftmailer_collection');

            } else {
                $this->addFlash('warning', 'Swifmailer with same name or email already exist.');
            }
        }

        return $this->render('@UVDeskCore//Swiftmailer//swiftmailerAdd.html.twig', array(
            'errors' => json_encode($errors)
        ));
    }

    // Edit Swiftmailer
    public function editSwiftmailer($swiftmailerId, Request $request)
    {
        $data = $request->request->all();
        $errors = [];
        $swiftmailerDetails = $this->getswiftmailerDetails($swiftmailerId);
        $filePath = dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml';
        $file = file($filePath);

        if($request->getMethod() == 'POST') {

            $isExistSwiftmailer = $this->checkExistingSwiftmailer($swiftmailerId, $data['username']);

            if($isExistSwiftmailer){
                $file_content_array = $this->getYamlContentAsArray($filePath);
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

            $file_content_array = $this->getYamlContentAsArray($filePath);
            
            if (isset($file_content_array['swiftmailer']) && $file_content_array['swiftmailer']['mailers']) {
                $existingSwiftmailerCount = sizeof($file_content_array['swiftmailer']['mailers']);
                $file_content_array['swiftmailer']['mailers'] = array_merge($file_content_array['swiftmailer']['mailers'], $newSwiftMailer);
            } else {
                $file_content_array['swiftmailer']['mailboxes'] = $newSwiftMailer;
            }
            $updateFile = $this->setYamlContent($filePath, $file_content_array);

            $this->addFlash('success', 'Swifmailer detail updated successfully.');

            return $this->redirectToRoute('helpdesk_member_swiftmailer_collection');
        }

        return $this->render('@UVDeskCore//Swiftmailer//swiftmailerEdit.html.twig', array(
            'errors' => json_encode($errors),
            'swiftmailerDetails' => $swiftmailerDetails
        ));

    }
 
    private function setYamlContent ($filePath, $arrayContent)
    {
        // Write the content with new mailbox details in file
        return file_put_contents($filePath, Yaml::dump($arrayContent, 6));
    }

    private function getYamlContentAsArray ($filePath)
    {
        // Fetch existing content in file
        $file_content = '';
        if ($fh = fopen($filePath, 'r')) {
            while (!feof($fh)) {
                $file_content = $file_content.fgets($fh);
            }
        }
        // Convert yaml file content into array and merge existing mailbox and new mailbox
        return Yaml::parse($file_content, 6);
    }
    public function arrayToString($array)
    {
        return implode(PHP_EOL, array_map(
            function ($v, $k) {
                if(is_array($v)){
                    return $k.'[]='.implode('&'.$k.'[]=', $v);
                }else{
                    return $k.': '.$v;
                }
            }, 
            $array, 
            array_keys($array)
        ));
    }
    private function checkExistingSwiftmailer ($uniqueId, $email = null)
    {
        $isExist = false;
        $file_content_array = $this->getYamlContentAsArray(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml');
        $existingSwiftmailer = $file_content_array['swiftmailer']['mailers'];
        if ($existingSwiftmailer) {
            foreach ($existingSwiftmailer as $index => $swiftmailerDetails) {
                if ($index == $uniqueId || $swiftmailerDetails['username'] == $email) {
                        $isExist = true;
                }
            }
        }
        return $isExist;
    }
    private function getswiftmailerDetails ($swiftmailerId)
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

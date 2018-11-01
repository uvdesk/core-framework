<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Webkul\UVDesk\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Webkul\UVDesk\CoreBundle\Workflow\Events as CoreWorkflowEvents;

class Customer extends Controller
{
    public function listCustomers(Request $request) 
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_CUSTOMER')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskCore/Customers/listSupportCustomers.html.twig');
    }

    public function createCustomer(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_CUSTOMER')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if ($request->getMethod() == "POST") {
            $entityManager = $this->getDoctrine()->getManager();
            $formDetails = $request->request->get('customer_form');
            $uploadedFiles = $request->files->get('customer_form');
            
            $user = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email' => $formDetails['email']));
            $customerInstance = !empty($user) ? $checkUser->getCustomerInstance() : null;

            if (empty($customerInstance)){
                if (!empty($formDetails)) {
                    $fullname = trim(implode(' ', [$formDetails['firstName'], $formDetails['lastName']]));
                    $supportRole = $entityManager->getRepository('UVDeskCoreBundle:SupportRole')->findOneByCode('ROLE_CUSTOMER');
    
                    $user = $this->container->get('user.service')->createUserInstance($formDetails['email'], $fullname, $supportRole, [
                        'contact' => $formDetails['contactNumber'],
                        'source' => 'website',
                        'active' => !empty($formDetails['isActive']) ? true : false,
                        'image' => $uploadedFiles['profileImage'],
                    ]);
    
                    // Trigger customer created event
                    $event = new GenericEvent(CoreWorkflowEvents\Customer\Create::getId(), [
                        'entity' => $user,
                    ]);
    
                    $this->get('event_dispatcher')->dispatch('uvdesk.automation.workflow.execute', $event);
    
                    $this->addFlash('success', 'Success ! Customer saved successfully.');
    
                    return $this->redirect($this->generateUrl('helpdesk_member_manage_customer_account_collection'));
                }
            } else {
                $this->addFlash('warning', 'Error ! User with same email already exist.');
            }
        }
        
        return $this->render('@UVDeskCore/Customers/createSupportCustomer.html.twig', [
            'user' => new User(),
            'errors' => json_encode([])
        ]);
    }

    public function editCustomer(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_CUSTOMER')) {          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('UVDeskCoreBundle:User');

        if($userId = $request->attributes->get('customerId')) {
            $user = $repository->findOneBy(['id' =>  $userId]);
            if(!$user)
                $this->noResultFound();
        }
        if ($request->getMethod() == "POST") {
            $contentFile = $request->files->get('customer_form');
            if($userId) {
                $data = $request->request->all();
                $data = $data['customer_form'];
                $checkUser = $em->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email' => $data['email']));
                $errorFlag = 0;

                if($checkUser) {
                    if($checkUser->getId() != $userId)
                        $errorFlag = 1;
                }
                
                if(!$errorFlag && 'hello@uvdesk.com' !== $user->getEmail()) {
                    $password = $user->getPassword();
                    $email = $user->getEmail();
                    $user->setFirstName($data['firstName']);
                    $user->setLastName($data['lastName']);
                    $user->setEmail($data['email']);
                    $user->setIsEnabled(isset($data['isActive']) ? 1 : 0);
                    $em->persist($user);

                    
                    $userInstance = $em->getRepository('UVDeskCoreBundle:UserInstance')->findOneBy(array('user' => $user->getId()));
                    $userInstance->setUser($user);
                    $userInstance->setIsActive(isset($data['isActive']) ? 1 : 0);
                    $userInstance->setIsVerified(0);
                    if(isset($data['source']))
                        $userInstance->setSource($data['source']);
                    else
                        $userInstance->setSource('website');
                    if(isset($data['contactNumber'])) {
                        $userInstance->setContactNumber($data['contactNumber']);
                    }
                    if(isset($contentFile['profileImage'])){
                        $fileName = $this->container->get('uvdesk.service')->getFileUploadManager()->upload($contentFile['profileImage']);
                        $userInstance->setProfileImagePath($fileName);
                    }
                        
                    $em->persist($userInstance);
                    $em->flush();
            
                    $user->addUserInstance($userInstance);
                    $em->persist($user);
                    $em->flush();

                    $this->addFlash('success', 'Success ! Customer information updated successfully.'); 
                    return $this->redirect($this->generateUrl('helpdesk_member_manage_customer_account_collection'));
                } else {
                    $this->addFlash('warning', 'Error ! User with same email is already exist.');
                }
            } 
        }
        
        return $this->render('@UVDeskCore/Customers/updateSupportCustomer.html.twig', [
                'user' => $user,
                'errors' => json_encode([])
        ]);
    }

    protected function encodePassword(User $user, $plainPassword)
    {
        $encoder = $this->container->get('security.encoder_factory')
                   ->getEncoder($user);

        return $encoder->encodePassword($plainPassword, $user->getSalt());
    }
    
    public function bookmarkCustomer(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_CUSTOMER')) {          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $json = array();
        $em = $this->getDoctrine()->getManager();
        $data = json_decode($request->getContent(), true);
        $id = $request->attributes->get('id') ? : $data['id'];
        $user = $em->getRepository('UVDeskCoreBundle:User')->findOneBy(['id' => $id]);
        if(!$user)  {
            $json['error'] = 'resource not found';
            return new JsonResponse($json, Response::HTTP_NOT_FOUND);
        }
        $userInstance = $em->getRepository('UVDeskCoreBundle:UserInstance')->findOneBy(array(
                'user' => $id,
                'supportRole' => 4
            )
        );

        if($userInstance->getIsStarred()) {
            $userInstance->setIsStarred(0);
            $em->persist($userInstance);
            $em->flush();
            $json['alertClass'] = 'success';
            $json['message'] = 'unstarred Action Completed successfully';             
        } else {
            $userInstance->setIsStarred(1);
            $em->persist($userInstance);
            $em->flush();
            $json['alertClass'] = 'success';
            $json['message'] = 'starred Action Completed successfully';             
        }
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
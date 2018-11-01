<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Webkul\UVDesk\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CustomerXHR extends Controller
{
    public function listCustomersXHR(Request $request) 
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_CUSTOMER')) {          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }
        
        $json = array();
        
        if($request->isXmlHttpRequest()) {
            $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:User');
            $json =  $repository->getAllCustomer($request->query, $this->container);
        }
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }

    public function removeCustomerXHR(Request $request) 
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_CUSTOMER')) {          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }
        
        $json = array();
        if($request->getMethod() == "DELETE") {
            $em = $this->getDoctrine()->getManager();
            $id = $request->attributes->get('customerId');
            $user = $em->getRepository('UVDeskCoreBundle:User')->findOneBy(['id' => $id]);

            if($user) {

                $this->get('user.service')->removeCustomer($user);
                $json['alertClass'] = 'success';
                $json['alertMessage'] = ('Success ! Customer removed successfully.');
            } else {
                $json['alertClass'] =  'danger';
                $json['alertMessage'] = ('Error ! Invalid customer id.');
                $json['statusCode'] = Response::HTTP_NOT_FOUND;
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
}

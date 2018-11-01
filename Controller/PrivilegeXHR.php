<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Webkul\UVDesk\CoreBundle\Entity;
use Webkul\UVDesk\CoreBundle\Entity\SupportPrivilege;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class PrivilegeXHR extends Controller
{
    public function listPrivilegeXHR(Request $request) 
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_AGENT_PRIVILEGE')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if (true === $request->isXmlHttpRequest()) {
            $paginationResponse = $this->getDoctrine()->getRepository('UVDeskCoreBundle:SupportPrivilege')->getAllPrivileges($request->query, $this->container);

            return new Response(json_encode($paginationResponse), 200, ['Content-Type' => 'application/json']);
        }
        
        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

    public function deletePrivilegeXHR($supportPrivilegeId)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_AGENT_PRIVILEGE')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }
        
        $request = $this->get('request_stack')->getCurrentRequest();

        if ("DELETE" == $request->getMethod()) {
            $entityManager = $this->getDoctrine()->getManager();
            $supportPrivilege = $entityManager->getRepository('UVDeskCoreBundle:SupportPrivilege')->findOneById($supportPrivilegeId);

            if (!empty($supportPrivilege)) {
                $entityManager->remove($supportPrivilege);
                $entityManager->flush();

                return new Response(json_encode([
                    'alertClass' => 'success',
                    'alertMessage' => 'Support Privilege removed successfully.',
                ]), 200, ['Content-Type' => 'application/json']);
            }
        }
        
        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

}

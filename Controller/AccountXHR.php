<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Webkul\UVDesk\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AccountXHR extends Controller
{
    public function listAgentsXHR(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_AGENT')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if (true === $request->isXmlHttpRequest()) {
            $userRepository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:User');
            $agentCollection = $userRepository->getAllAgents($request->query, $this->container);
            return new Response(json_encode($agentCollection), 200, ['Content-Type' => 'application/json']);
        } 
        return new Response(json_encode([]), 404);
    }

    public function deleteAgent(Request $request)
    {
        if($request->getMethod() == "DELETE") {
            $em = $this->getDoctrine()->getManager();
            $id = $request->query->get('id');
            /*
                Original Code: $user = $em->getRepository('WebkulUserBundle:User')->findUserByCompany($id,$company->getId());
                Using findUserByCompany() won't execute the UserListener, so user roles won't be set and user with ROLE_SUPER_ADMIN can be deleted as a result.
                To trigger UserListener to set roles, you need to only select 'u' instead of both 'u, dt' in query select clause.
                Doing this here instead of directly making changes to userRepository->findUserByCompany().
             */
            $user = $em->createQuery('SELECT u FROM UVDeskCoreBundle:User u JOIN u.userInstance userInstance WHERE u.id = :userId  AND userInstance.supportRole != :roles')
                ->setParameter('userId', $id)
                ->setParameter('roles', 4)
                ->getOneOrNullResult();

            if ($user) {
                if($user->getAgentInstance()->getSupportRole() != "ROLE_SUPER_ADMIN") {
                    $this->get('user.service')->removeAgent($user);
                    $json['alertClass'] = 'success';
                    $json['alertMessage'] = ('Success ! Agent removed successfully.');
                } else {
                    $json['alertClass'] = 'warning';
                    $json['alertMessage'] = $this->translate("Warning ! You are allowed to remove account owner's account.");
                }
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = $this->translate('Error ! Invalid user id.');
            }
        }
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    } 
}

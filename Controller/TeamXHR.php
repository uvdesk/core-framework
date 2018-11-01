<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Webkul\UVDesk\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class TeamXHR extends Controller
{
    public function listTeamsXHR(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_SUB_GROUP')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if (true === $request->isXmlHttpRequest()) {
            $paginationResponse = $this->getDoctrine()->getRepository('UVDeskCoreBundle:SupportTeam')->getAllSupportTeams($request->query, $this->container);

            return new Response(json_encode($paginationResponse), 200, ['Content-Type' => 'application/json']);
        }

        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

    public function deleteTeamXHR($supportTeamId)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_SUB_GROUP')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ("DELETE" == $request->getMethod()) {
            $entityManager = $this->getDoctrine()->getManager();
            $supportTeam = $entityManager->getRepository('UVDeskCoreBundle:SupportTeam')->findOneById($supportTeamId);

            if (!empty($supportTeam)) {
                $entityManager->remove($supportTeam);
                $entityManager->flush();

                return new Response(json_encode([
                    'alertClass' => 'success',
                    'alertMessage' => 'Support Team removed successfully.',
                ]), 200, ['Content-Type' => 'application/json']);
            }
        }
        
        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }
}

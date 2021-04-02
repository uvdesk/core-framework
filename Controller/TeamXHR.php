<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Symfony\Component\Translation\TranslatorInterface;

class TeamXHR extends Controller
{
    private $userService;
    private $translator;
    
    public function __construct(UserService $userService, TranslatorInterface $translator)
    {
        $this->userService = $userService;
        $this->translator = $translator;
    }

    public function listTeamsXHR(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_SUB_GROUP')){
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if (true === $request->isXmlHttpRequest()) {
            $paginationResponse = $this->getDoctrine()->getRepository('UVDeskCoreFrameworkBundle:SupportTeam')->getAllSupportTeams($request->query, $this->container);

            return new Response(json_encode($paginationResponse), 200, ['Content-Type' => 'application/json']);
        }

        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

    public function deleteTeamXHR($supportTeamId, TranslatorInterface $translator)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_SUB_GROUP')){
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ("DELETE" == $request->getMethod()) {
            $entityManager = $this->getDoctrine()->getManager();
            $supportTeam = $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportTeam')->findOneById($supportTeamId);

            if (!empty($supportTeam)) {
                $entityManager->remove($supportTeam);
                $entityManager->flush();
                
                return new Response(json_encode([
                    'alertClass' => 'success',
                    'alertMessage' => $this->translator->trans('Support Team removed successfully.'),
                ]), 200, ['Content-Type' => 'application/json']);
            }
        }

        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }
}

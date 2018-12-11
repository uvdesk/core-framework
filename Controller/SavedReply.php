<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Webkul\UVDesk\CoreBundle\Form as CoreBundleForms;
use Webkul\UVDesk\CoreBundle\Utils\HTMLFilter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webkul\UVDesk\CoreBundle\Entity as CoreBundleEntities;

class SavedReply extends Controller
{
    const ROLE_REQUIRED = 'saved_replies';
    const LIMIT = 10;

    protected function getTemplate($request)
    {
       return $this->getDoctrine()
                   ->getRepository('UVDeskCoreBundle:SavedReplies')
                   ->findOneBy([
                                'id' => $request->attributes->get('template'),
                                'user' => $this->getUser()->getAgentInstance(),
                            ]);
    }

    protected function getTemplates($request, $full = false)
    {
        if((int)$request->query->get('page') < 0) return [];
        $queryBuilder = $this->getDoctrine()
                             ->getRepository('UVDeskCoreBundle:SavedReplies')
                             ->createQueryBuilder('s');

        $limit = self::LIMIT;
        $page = abs((int)$request->query->get('page'));
        if($request->query->get('init')){
            $offset = 0;
            $limit *= $page ? $page : 1;
        }
        else
            $offset = $page ? ((($page-1) * $limit )) : 0;

        if($full) {
            $queryBuilder->select('s');
        } else {
            $queryBuilder->select('sid, s.name');
        }

        $qb = $queryBuilder->where('s.name LIKE :name')
                         ->andWhere('s.user = :user')
                         ->orderBy(
                                $request->query->get('sort') ? 's.'.$request->query->get('sort') : 's.id',
                                $request->query->get('direction') ? $request->query->get('direction') : Criteria::DESC
                            )
                         ->setParameters(
                            array(
                                    'name' => '%'.$request->query->get('search').'%',
                                    'user' => $this->getUser()->getAgentInstance(),
                                )
                            )
                          ->setFirstResult( $offset )
                          ->setMaxResults( $limit );
        $results = $qb->getQuery()->getArrayResult();

        return $results;
    }

    public function templates(Request $request) 
    {        
        return $this->render('@UVDeskCore//savedRepliesList.html.twig');
    }

    public function savedReplyForm(Request $request) 
    {
        $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:SavedReplies');
        if($request->attributes->get('template'))
            $template = $repository->getSavedReply($request->attributes->get('template'), $this->container);
        else
            $template = new CoreBundleEntities\SavedReplies();

        if(!$template)
            $this->noResultFound();

        if(!$template->getMessage())
                $template->setMessage('<p><br></p>');


        $errors = [];
        if($request->getMethod() == 'POST') {
            // $form = $this->createForm(new Form\EmailTemplates, $template, ['validation_groups' => ['savedReply']]);
            // $form->handleRequest($request);

            // if($form->isValid() && $form->isSubmitted()) {
                $em = $this->getDoctrine()->getManager();
                $template->setName($request->request->get('name'));
                //if($this->get('user.service')->checkPermission('ROLE_ADMIN')) {
                    /* groups */ 
                    $groups = explode(',', $request->request->get('tempGroups'));
                    $previousGroupIds = [];
                    if($template->getSupportGroups()) {
                        foreach($template->getSupportGroups() as $key => $group) {
                            $previousGroupIds[] = $group->getId();
                            if(!in_array($group->getId(), $groups ) ) {
                                $template->removeSupportGroups($group);
                                $em->persist($template);
                            }
                        }
                    }
                    foreach($groups as $key => $groupId) {
                        if($groupId) {
                            $group = $em->getRepository('UVDeskCoreBundle:SupportGroup')->findOneBy([ 'id' => $groupId ]);
                            if($group && (empty($previousGroupIds) || !in_array($groupId, $previousGroupIds)) ) {
                                $template->addSupportGroup($group);
                                $em->persist($template);
                            }
                        }
                    }

                    /* teams */
                    $teams = explode(',', $request->request->get('tempTeams'));
                    $previousTeamIds = [];
                    if($template->getSupportTeams()) {
                        foreach($template->getSupportTeams() as $key => $team) {
                            $previousTeamIds[] = $team->getId();
                            if(!in_array($team->getId(), $teams ) ) {
                                $template->removeSupportTeam($team);
                                $em->persist($template);
                            }
                        }
                    }
                    foreach($teams as $key => $teamId) {
                        if($teamId) {
                            $team = $em->getRepository('UVDeskCoreBundle:SupportTeam')->findOneBy([ 'id' => $teamId ]);
                            if($team && (empty($previousTeamIds) || !in_array($teamId, $previousTeamIds)) ) {
                                $template->addSupportTeam($team);
                                $em->persist($template);
                            }
                        }
                    }
               // }
               $htmlFilter = new HTMLFilter();      

                //htmlfilter these values
                $template->setMessage($request->request->get('message'));
                if(empty($template->getUser()))  {
                    $template->setUser($this->getUser()->getAgentInstance());
                }
                $em->persist($template);
                $em->flush();

                $this->addFlash(
                        'success',
                        $request->attributes->get('template')?
                        'Success! Reply has been updated successfully.'
                        : 'Success! Reply has been added successfully.'
                    );
                return $this->redirectToRoute('helpdesk_member_Saved_Reply');
            // } else {
            //     $errors = $this->getFormErrors($form);
            // }
        }

        return $this->render('@UVDeskCore//savedReplyForm.html.twig', array(
            'template' => $template,
            'errors' => json_encode($errors)
        ));
    } 

    public function templatesxhr(Request $request) 
    {
        $json = array();
        $error = false;
        if($request->isXmlHttpRequest()){
            if($request->getMethod() == 'GET'){
                $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:SavedReplies');
                $json =  $repository->getSavedReplies($request->query, $this->container);
            }else{
                if($request->attributes->get('template')){
                    if($templateBase = $this->getTemplate($request)){
                        if($request->getMethod() == 'DELETE'){
                            $em = $this->getDoctrine()->getManager();
                            $em->remove($templateBase);
                            $em->flush();

                            $json['alertClass'] = 'success';
                            $json['alertMessage'] = 'Success! Saved Reply has been deleted successfully.';
                        }else
                            $error = true;
                    }else{
                        $error = true;
                    }
                }
            }
        }

        if($error){
            $json['alertClass'] = 'error';
            $json['alertMessage'] = 'Warning! You are not allowed to perform this action.';
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    private function getId($item)
    {
        return $item->getId();
    }
}

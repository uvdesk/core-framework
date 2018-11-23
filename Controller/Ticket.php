<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Webkul\UVDesk\CoreBundle\Form as CoreBundleForms;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webkul\UVDesk\CoreBundle\Entity as CoreBundleEntities;
use Webkul\UVDesk\CoreBundle\DataProxies as CoreBundleDataProxies;

class Ticket extends Controller
{
    public function listTicketCollection(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        return $this->render('@UVDeskCore//ticketList.html.twig', [
            'ticketStatusCollection' => $entityManager->getRepository('UVDeskCoreBundle:TicketStatus')->findAll(),
            'ticketTypeCollection' => $entityManager->getRepository('UVDeskCoreBundle:TicketType')->findAll(),
            'ticketPriorityCollection' => $entityManager->getRepository('UVDeskCoreBundle:TicketPriority')->findAll(),
        ]);
    }

    public function loadTicket($ticketId)
    {
        // $this->get('event.manager')->trigger([
        //     'subject' => ($thread->getThreadType() == 'forward') ? $data['subject'] : '',
        //     'event' => 'ticket.'.$thread->getThreadType().'.added',
        //     'entity' => $thread->getTicket(),
        //     'targetEntity' => $thread,
        //     'socialMedium' => (!empty($data['threadProcess']) && $data['threadProcess'] == 'social-reply') ? true : false,
        // ]);

        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $userRepository = $entityManager->getRepository('UVDeskCoreBundle:User');
        $ticketRepository = $entityManager->getRepository('UVDeskCoreBundle:Ticket');

        $ticket = $ticketRepository->findOneById($ticketId);

        if (empty($ticket)) {
            throw new \Exception('Page not found');
        } else {
            // $this->denyAccessUnlessGranted('VIEW', $ticket);

            // Mark as viewed by agents
            if (false == $ticket->getIsAgentViewed()) {
                $ticket->setIsAgentViewed(true);

                $entityManager->persist($ticket);
                $entityManager->flush();
            }
        }

        // ( in_array($this->getUser()->getRole(), ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']) 
        // ?: (in_array('ROLE_AGENT_AGENT_KICK', $this->get('user.service')->getAgentPrivilege($this->getUser()->getId()))) )

        $agent = $ticket->getAgent();
        $customer = $ticket->getCustomer();
        $user = $this->get('user.service')->getSessionUser();

        return $this->render('@UVDeskCore//ticket.html.twig', [
            'ticket' => $ticket,
            'totalReplies' => $ticketRepository->countTicketTotalThreads($ticket->getId()),
            'totalCustomerTickets' => $ticketRepository->countCustomerTotalTickets($user),
            'initialThread' => $this->get('ticket.service')->getTicketInitialThreadDetails($ticket),
            'ticketAgent' => !empty($agent) ? $agent->getAgentInstance()->getPartialDetails() : null,
            'customer' => $customer->getCustomerInstance()->getPartialDetails(),
            'currentUserDetails' => $user->getAgentInstance()->getPartialDetails(),
            'supportGroupCollection' => $userRepository->getSupportGroups(),
            'supportTeamCollection' => $userRepository->getSupportTeams(),
            'ticketStatusCollection' => $entityManager->getRepository('UVDeskCoreBundle:TicketStatus')->findAll(),
            'ticketTypeCollection' => $entityManager->getRepository('UVDeskCoreBundle:TicketType')->findAll(),
            'ticketPriorityCollection' => $entityManager->getRepository('UVDeskCoreBundle:TicketPriority')->findAll(),
            'ticketNavigationIteration' => $ticketRepository->getTicketNavigationIteration($ticket),
            'ticketLabelCollection' => $ticketRepository->getTicketLabelCollection($ticket, $user),
        ]);
    }

    public function getSearchFilterOptionsXhr(Request $request)
    {
        $json = [];
        if($request->isXmlHttpRequest()) {
            if($request->query->get('type') == 'agent') {
                $json = $this->get('user.service')->getAgentsPartialDetails($request);
            } elseif($request->query->get('type') == 'customer') {
                $json = $this->get('user.service')->getCustomersPartial($request);
            } elseif($request->query->get('type') == 'group') {
                $json = $this->get('user.service')->getGroups($request);
            } elseif($request->query->get('type') == 'team') {
                $json = $this->get('user.service')->getSubGroups($request);
            } elseif($request->query->get('type') == 'tag') {
                $json = $this->get('ticket.service')->getTicketTags($request);
            } elseif($request->query->get('type') == 'label') {
                $json = $this->get('ticket.service')->getLabels($request);
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function saveTicket(Request $request)
    {
        $requestParams = $request->request->all();
        $entityManager = $this->getDoctrine()->getManager();
        $response = $this->redirect($this->generateUrl('helpdesk_member_ticket_collection'));

        if ($request->getMethod() != 'POST' || false == $this->get('user.service')->isAccessAuthorized('ROLE_AGENT_CREATE_TICKET')) {
            return $response;
        }
        
        // Get referral ticket if any
        $ticketValidationGroup = 'CreateTicket';
        $referralURL = $request->headers->get('referer');

        if (!empty($referralURL)) {
            $iterations = explode('/', $referralURL);
            $referralId = array_pop($iterations);
            $expectedReferralURL = $this->generateUrl('helpdesk_member_ticket', ['ticketId' => $referralId], UrlGeneratorInterface::ABSOLUTE_URL);

            if ($referralURL === $expectedReferralURL) {
                $referralTicket = $entityManager->getRepository('UVDeskCoreBundle:Ticket')->findOneById($referralId);
                
                if (!empty($referralTicket)) {
                    $ticketValidationGroup = 'CustomerCreateTicket';
                }
            }
        }

        $ticketType = $entityManager->getRepository('UVDeskCoreBundle:TicketType')->findOneById($requestParams['type']);

        $ticketProxy = new CoreBundleDataProxies\CreateTicketDataClass();
        $form = $this->createForm(CoreBundleForms\CreateTicket::class, $ticketProxy);
        // Validate Ticket Details
        $form->submit($requestParams);
        if (false == $form->isSubmitted() || false == $form->isValid()) {
            if (false === $form->isValid()) {
                dump($form->getErrors(true));
                die;
            }

            return $this->redirect(!empty($referralURL) ? $referralURL : $this->generateUrl('helpdesk_member_ticket_collection'));
        }
        
        if ('CustomerCreateTicket' === $ticketValidationGroup && !empty($referralTicket)) {
            // Retrieve customer details from referral ticket
            $customer = $referralTicket->getCustomer();
            $customerPartialDetails = $customer->getCustomerInstance()->getPartialDetails();
        } else if (null != $ticketProxy->getFrom() && null != $ticketProxy->getName()) {
            // Create customer if account does not exists
            $customer = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneByEmail($ticketProxy->getFrom());

            if (empty($customer) || null == $customer->getCustomerInstance()) {
                $role = $entityManager->getRepository('UVDeskCoreBundle:SupportRole')->findOneByCode('ROLE_CUSTOMER');
                
                // Create User Instance
                $customer = $this->get('user.service')->createUserInstance($ticketProxy->getFrom(), $ticketProxy->getName(), $role, [
                    'source' => 'website',
                    'active' => true
                ]);
            }
        }

        $ticketData = [
            'from' => $customer->getEmail(),
            'name' => $customer->getFirstName() . ' ' . $customer->getLastName(),
            'type' => $ticketProxy->getType(),
            'subject' => $ticketProxy->getSubject(),
            'message' => htmlentities($ticketProxy->getReply()),
            'firstName' => $customer->getFirstName(),
            'lastName' => $customer->getLastName(),
            'type' => $ticketProxy->getType(),
            'role' => 4,
            'source' => 'website',
            'threadType' => 'create',
            'createdBy' => 'agent',
            'customer' => $customer,
            'user' => $this->getUser(),
            'attachments' => $request->files->get('attachments'),
        ];
       
        $thread = $this->get('ticket.service')->createTicketBase($ticketData);

        if (!empty($thread)) {
            $ticket = $thread->getTicket();
            $request->getSession()->getFlashBag()->set('success', sprintf('Success! Ticket #%s has been created successfully.', $ticket->getId()));

            if ($this->get('user.service')->isAccessAuthorized('ROLE_ADMIN')) {
                return $this->redirect($this->generateUrl('helpdesk_member_ticket', ['ticketId' => $ticket->getId()]));
            }
        } else {
            $request->getSession()->getFlashBag()->set('warning', 'Could not create ticket, invalid details.');
        }

        return $this->redirect(!empty($referralURL) ? $referralURL : $this->generateUrl('helpdesk_member_ticket_collection'));
    }

    public function listTicketTypeCollection(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_TICKET_TYPE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskCore/ticketTypeList.html.twig');
    }

    public function ticketType(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_TICKET_TYPE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $errorContext = [];
        $em = $this->getDoctrine()->getManager();

        if($id = $request->attributes->get('ticketTypeId')) {
            $type = $em->getRepository('UVDeskCoreBundle:TicketType')->find($id);
            if(!$type)
                $this->noResultFound();
        } else {
            $type = new CoreBundleEntities\TicketType();
        }

        if ($request->getMethod() == "POST") {
            $data = $request->request->all();
            $type->setCode($data['code']);
            $type->setDescription($data['description']);
            $type->setIsActive(isset($data['isActive']) ? 1 : 0);

            $em->persist($type);
            $em->flush();

            if(!$request->attributes->get('id')) {
                $request->getSession()->getFlashBag()->set('success', sprintf('Success! Ticket type saved successfully.'));
            } else {
                $request->getSession()->getFlashBag()->set('success', sprintf('Success! Ticket type updated successfully.'));
            }

            return $this->redirect($this->generateUrl('helpdesk_member_ticket_type_collection'));
        }

        return $this->render('@UVDeskCore/ticketTypeAdd.html.twig', array(
            'type' => $type,
            'errors' => json_encode($errorContext)
        ));
    }

    public function listTagCollection(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_TAG')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $enabled_bundles = $this->container->getParameter('kernel.bundles');

        return $this->render('@UVDeskCore/supportTagList.html.twig', [
            'articlesEnabled' => in_array('UVDeskSupportCenterBundle', array_keys($enabled_bundles)),
        ]);
    }

    public function removeTicketTagXHR($tagId, Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_TAG')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $json = [];
        if($request->getMethod() == "DELETE") {
            $em = $this->getDoctrine()->getManager();
            $tag = $em->getRepository('UVDeskCoreBundle:Tag')->find($tagId);
            if($tag) {
                $em->remove($tag);
                $em->flush();
                $json['alertClass'] = 'success';
                $json['alertMessage'] = 'Success ! Tag removed successfully.';
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function trashTicket(Request $request)
    {
        $ticketId = $request->attributes->get('ticketId');
        $entityManager = $this->getDoctrine()->getManager();
        $ticket = $entityManager->getRepository('UVDeskCoreBundle:Ticket')->find($ticketId);

        if (!$ticket) {
            $this->noResultFound();
        }
        
        if (!$ticket->getIsTrashed()) {
            $ticket->setIsTrashed(1);

            $entityManager->persist($ticket);
            $entityManager->flush();
        }

        $this->addFlash('warning','Success ! Ticket moved to trash successfully.');

        return $this->redirectToRoute('helpdesk_member_ticket_collection');
    }

    public function downloadZipAttachment(Request $request)
    {
        $threadId = $request->attributes->get('threadId');
        $attachmentRepository = $this->getDoctrine()->getManager()->getRepository('UVDeskCoreBundle:Attachment');

        $attachment = $attachmentRepository->findByThread($threadId);

        if (!$attachment) {
            $this->noResultFound();
        }

        $zipname = 'attachments/' .$threadId.'.zip';
        $zip = new \ZipArchive;

        $zip->open($zipname, \ZipArchive::CREATE);
        if (count($attachment)) {
            foreach ($attachment as $attach) {
                $zip->addFile(substr($attach->getPath(), 1)); 
            }
        }

        $zip->close();

        $response = new Response();
        $response->setStatusCode(200);
        $response->headers->set('Content-type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $threadId . '.zip');
        $response->sendHeaders();
        $response->setContent(readfile($zipname));

        return $response;
    }

    // Tag add for ticket
    public function createTicketTagXHR(Request $request) { 
        $json = [];
        $content = json_decode($request->getContent(), true);

        $em = $this->getDoctrine()->getManager();
        $ticket = $em->getRepository('UVDeskCoreBundle:Ticket')->find($content['ticketId']);
        if($request->getMethod() == "POST") {
            $tag = new CoreBundleEntities\Tag();
            if ($content['name'] != "") {
                $checkTag = $em->getRepository('UVDeskCoreBundle:Tag')->findOneBy(array('name' => $content['name']));
                if(!$checkTag) {
                    $tag->setName($content['name']);
                    $em->persist($tag);
                    $em->flush();
                    //$json['tag'] = json_decode($this->objectSerializer($tag));
                    $ticket->addSupportTag($tag);
                } else {
                    //$json['tag'] = json_decode($this->objectSerializer($checkTag));
                    $ticket->addSupportTag($checkTag);
                }
                $em->persist($ticket);
                $em->flush();
                $json['alertClass'] = 'success';
                $json['alertMessage'] = 'Success ! Tag added successfully.';
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = 'Please enter tag name.';
            }
        } elseif($request->getMethod() == "DELETE") {
            $tag = $em->getRepository('UVDeskCoreBundle:Tag')->findOneBy(array('id' => $request->attributes->get('id')));
            if($tag) {
                $articles = $em->getRepository('UVDeskSupportCenterBundle:ArticleTags')->findOneBy(array('tagId' => $tag->getId()));
                if($articles)
                    foreach ($articles as $entry) {
                        $em->remove($entry);
                    }

                $ticket->removeSupportTag($tag);
                $em->persist($ticket);
                $em->flush();
                $json['alertClass'] = 'success';
                $json['alertMessage'] = 'Success ! Tag unassigned successfully.';

            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = 'Error ! Invalid tag.';
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
        /**
     * ticketCollaboratorXhrAction "Ticket Collaborator controller action"
     * @param Object $request "HTTP Request object"
     */
    public function updateCollaboratorXHR(Request $request) {

        $json = [];
        $content = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();
        $ticket = $em->getRepository('UVDeskCoreBundle:Ticket')->find($content['ticketId']);
        if($request->getMethod() == "POST") {
            if($content['email'] == $ticket->getCustomer()->getEmail()) {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = $this->get('translator')->trans('Error ! Can not add customer as a collaborator.');
            } else {
                $data = array(
                        'from' => $content['email'],
                        'firstName' => ($firstName = ucfirst(current(explode('@', $content['email'])))),
                        'lastName' => ' ',
                        'role' => 4,
                    );
                
                $collaborator = $this->get('user.service')->getUserDetails($data);
                $checkTicket = $em->getRepository('UVDeskCoreBundle:Ticket')->isTicketCollaborator($ticket,$content['email']);
                if(!$checkTicket) {
                    $ticket->addCollaborator($collaborator);
                    $em->persist($ticket);
                    $em->flush();

                    $ticket->lastCollaborator = $collaborator;
                   
                    $json['collaborator'] =  $this->get('user.service')->getCustomerPartialDetailById($collaborator->getId());
                    $json['alertClass'] = 'success';
                    $json['alertMessage'] = $this->get('translator')->trans('Success ! Collaborator added successfully.');
                } else {
                    $json['alertClass'] = 'danger';
                    $json['alertMessage'] = $this->get('translator')->trans('Error ! Collaborator is already added.');
                }
            }
        } elseif($request->getMethod() == "DELETE") {
            $collaborator = $em->getRepository('UVDeskCoreBundle:User')->findOneBy(array('id' => $request->attributes->get('id')));
            if($collaborator) {
                $ticket->removeCollaborator($collaborator);
                $em->persist($ticket);
                $em->flush();

                $json['alertClass'] = 'success';
                $json['alertMessage'] = $this->get('translator')->trans('Success ! Collaborator removed successfully.');
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = $this->get('translator')->trans('Error ! Invalid Collaborator.');
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
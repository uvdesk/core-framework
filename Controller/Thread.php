<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Utils\HTMLFilter;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Webkul\UVDesk\CoreBundle\Workflow\Events as CoreWorkflowEvents;

class Thread extends Controller
{
    public function saveThread($ticketId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $params = $request->request->all();
        $ticket = $entityManager->getRepository('UVDeskCoreBundle:Ticket')->findOneById($ticketId);

        // Validate Request
        if (empty($ticket)) {
            throw new \Exception('Ticket not found', 404);
        } else if ('POST' !== $request->getMethod()) {
            throw new \Exception('Invalid Request', 403);
        } else {
            // Validate user permission if the thread being added is a note
            if ('note' == $params['threadType']) {
                if (false == $this->get('user.service')->isAccessAuthorized('ROLE_AGENT_ADD_NOTE')) {
                    throw new \Exception('Insufficient Permisions', 400);
                }
            }

            // // Deny access unles granted ticket view permission
            // $this->denyAccessUnlessGranted('AGENT_VIEW', $ticket);

            // Check if reply content is empty
            $parsedMessage = trim(strip_tags($params['reply'], '<img>'));
            $parsedMessage = str_replace('&nbsp;', '', $parsedMessage);
            $parsedMessage = str_replace(' ', '', $parsedMessage);
            
            if (null == $parsedMessage) {
                $this->addFlash('warning', "Reply content cannot be left blank.");
            }

            // @TODO: Validate file attachments
            // if (true !== $this->get('file.service')->validateAttachments($request->files->get('attachments'))) {
            //     $this->addFlash('warning', "Invalid attachments.");
            // }
        }
        
        $threadDetails = [
            'user' => $this->getUser(),
            'createdBy' => 'agent',
            'source' => 'website',
            'threadType' => strtolower($params['threadType']),
            'message' => $params['reply'],
            'attachments' => $request->files->get('attachments')
        ];
        
        if (isset($params['to'])) {
            $threadDetails['to'] = $params['to'];
        }
        
        if (isset($params['cc'])) {
            $threadDetails['cc'] = $params['cc'];
        }

        if (isset($params['bcc'])) {
            $threadDetails['bcc'] = $params['bcc'];
        }

        // Create Thread
        $thread = $this->get('ticket.service')->createThread($ticket, $threadDetails);
        // $this->addFlash('success', ucwords($params['threadType']) . " added successfully.");

        // @TODO: Remove Agent Draft Thread
        // @TODO: Trigger Thread Created Event
        
        // Trigger agent reply event
        $event = new GenericEvent(CoreWorkflowEvents\Ticket\AgentReply::getId(), [
            'entity' =>  $ticket,
        ]);
      
        $this->get('event_dispatcher')->dispatch('uvdesk.automation.workflow.execute', $event);

        // Check if ticket status needs to be updated
        $updateTicketToStatus = !empty($params['status']) ? (trim($params['status']) ?: null) : null;
       
        if (!empty($updateTicketToStatus) && $this->get('user.service')->isAccessAuthorized('ROLE_AGENT_UPDATE_TICKET_STATUS')) {
            $ticketStatus = $entityManager->getRepository('UVDeskCoreBundle:TicketStatus')->findOneById($updateTicketToStatus);

            if (!empty($ticketStatus) && $ticketStatus->getId() === $ticket->getStatus()->getId()) {
                $ticket->setStatus($ticketStatus);

                $entityManager->persist($ticket);
                $entityManager->flush();

                // @TODO: Trigger Ticket Status Updated Event
            }
        }
       
        // Redirect to either Ticket View | Ticket Listings
        if ('redirect' === $params['nextView']) {
            return $this->redirect($this->generateUrl('helpdesk_member_ticket_collection'));
        }
        
        $request->getSession()->getFlashBag()->set('success', ('Success! Reply has been added successfully'));
        return $this->redirect($this->generateUrl('helpdesk_member_ticket', ['ticketId' => $ticket->getId()]));
    }

    public function updateThreadXHR(Request $request)
    {
        $json = [];
        $em = $this->getDoctrine()->getManager();
        $content = json_decode($request->getContent(), true);
        
        if ($request->getMethod() == "PUT") {
            // $this->isAuthorized('ROLE_AGENT_EDIT_THREAD_NOTE');
            if (str_replace(' ','',str_replace('&nbsp;','',trim(strip_tags($content['reply'], '<img>')))) != "") {
                $htmlFilter = new HTMLFilter();
                $thread = $em->getRepository('UVDeskCoreBundle:Thread')->find($request->attributes->get('threadId'));
                $thread->setMessage(autolink($htmlFilter->HTMLFilter($content['reply'], '')));

                $em->persist($thread);
                $em->flush();
                
                $ticket = $thread->getTicket();
                $ticket->currentThread = $thread;

                // Trigger agent reply event
                $event = new GenericEvent(CoreWorkflowEvents\Ticket\ThreadUpdate::getId(), [
                    'entity' =>  $ticket,
                ]);

                $this->get('event_dispatcher')->dispatch('uvdesk.automation.workflow.execute', $event);

                $json['alertMessage'] = 'Success ! Thread updated successfully.';
                $json['alertClass'] = 'success';
            } else {
                $json['alertMessage'] = 'Error ! Reply field can not be blank.';
                $json['alertClass'] = 'error';
            }
        }  

        return new Response(json_encode($json), 200, ['Content-Type' => 'application/json']);
    }

    public function threadXHR(Request $request)
    {
        $json = array();
        $content = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();
        
        if ($request->getMethod() == "DELETE") {
            $thread = $em->getRepository('UVDeskCoreBundle:Thread')->findOneBy(array('id' => $request->attributes->get('threadId'), 'ticket' => $content['ticketId']));
            
            if ($thread) {
                // Trigger thread deleted event
                //  $event = new GenericEvent(CoreWorkflowEvents\Ticket\ThreadUpdate::getId(), [
                //     'entity' =>  $ticket,
                // ]);
                // $this->get('event_dispatcher')->dispatch('uvdesk.automation.workflow.execute', $event);

                $em->remove($thread);
                $em->flush();
                $json['alertClass'] = 'success';
                $json['alertMessage'] = 'Success ! Thread removed successfully.';
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = 'Error ! Invalid thread.';
            }
        } elseif ($request->getMethod() == "PATCH") {
            $thread = $em->getRepository('UVDeskCoreBundle:Thread')->findOneBy(array('id' => $request->attributes->get('threadId'), 'ticket' => $content['ticketId']));

            if ($thread) {
                if ($content['updateType'] == 'lock') { 
                    $thread->setIsLocked($content['isLocked']);
                    $em->persist($thread);
                    $em->flush();
                    
                    $json['alertMessage'] = $content['isLocked'] ? 'Success ! Thread locked successfully.' : 'Success ! Thread unlocked successfully.';
                    $json['alertClass'] = 'success';
                } elseif ($content['updateType'] == 'bookmark') {
                    $thread->setIsBookmarked($content['bookmark']);
                    $em->persist($thread);
                    $em->flush();

                    $json['alertMessage'] = $content['bookmark'] ? 'Success ! Thread pinned successfully.' : 'Success ! unpinned removed successfully.';
                    $json['alertClass'] = 'success';
                }
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = 'Error ! Invalid thread.';
            }
        }

        return new Response(json_encode($json), 200, ['Content-Type' => 'application/json']);
    }
}

<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
        
        if(isset($params['to']))
            $threadDetails['to'] = $params['to'];
        
        // Create Thread
        $thread = $this->get('ticket.service')->createThread($ticket, $threadDetails);
        // $this->addFlash('success', ucwords($params['threadType']) . " added successfully.");

        // @TODO: Remove Agent Draft Thread
        // @TODO: Trigger Thread Created Event
        
        // Trigger agent reply event
        $event = new GenericEvent('uvdesk.ticket.agent_reply', [
            'entity' => $ticket,
        ]);
      
        $this->get('event_dispatcher')->dispatch('uvdesk.automation.workflow.execute', $event);

        // Check if ticket status needs to be updated
        $updateTicketToStatus = !empty($params['status']) ? (trim($params['status']) ?: null) : null;
       
        if (!empty($updateTicketToStatus) && $this->get('user.service')->isAccessAuthorized('ROLE_AGENT_UPDATE_TICKET_STATUS')) {
            $ticketStatus = $em->getRepository('UVDeskCoreBundle:TicketStatus')->findOneById($updateTicketToStatus);

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
        
        return $this->redirect($this->generateUrl('helpdesk_member_ticket', ['ticketId' => $ticket->getId()]));
    }
}

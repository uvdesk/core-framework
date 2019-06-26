<?php

namespace Webkul\UVDesk\CoreBundle\Fixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Webkul\UVDesk\CoreBundle\Entity as CoreEntities;
use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;

class WelcomeTicket extends DoctrineFixture
{
    public function load(ObjectManager $entityManager)
    {
        $availableTicketPriority = $entityManager->getRepository('UVDeskCoreBundle:TicketPriority')->findOneBy(['code' => 'low']);
        $availableTicketStatus     = $entityManager->getRepository('UVDeskCoreBundle:TicketStatus')->findOneBy(['code' => 'open']);
        $supportRole = $entityManager->getRepository('UVDeskCoreBundle:SupportRole')->findOneBy(['code' => 'ROLE_CUSTOMER']);
        $superAdmin = $entityManager->getRepository('UVDeskCoreBundle:UserInstance')->findOneBy(['supportRole' => 1])->getUser();;
        
        $ticketParameter = $this->container->get('uvdesk.service')->getWelcomeTicketsParameters();
        
        if(!empty($superAdmin))
        {
            // Setting user details:
            $user = new CoreEntities\User();
            $user->setEmail($ticketParameter['userParameter']['customerEmail']);
            $user->setFirstName($ticketParameter['userParameter']['customerFirstName']);
            $user->setLastName($ticketParameter['userParameter']['customerLastName']);
            $user->setIsEnabled(true);

            $entityManager->persist($user);
            $entityManager->flush();

            // Setting user Instance:
            $userInstance = new CoreEntities\UserInstance();
            $userInstance->setUser($user);
            $userInstance->setSupportRole($supportRole);
            $userInstance->setDesignation(null);
            $userInstance->setSignature(null);
            $userInstance->setSource('website');
            $userInstance->setIsActive(true);
            $userInstance->setIsVerified(true);

            $entityManager->persist($userInstance);
            $entityManager->flush();

            // setting up ticket Data
            $ticket =  new CoreEntities\Ticket();
            $ticket->setSource('website');
            $ticket->setCustomer($user);
            $ticket->setAgent($superAdmin);
            $ticket->setSubject($ticketParameter['ticketParameters']['subject']);
            $ticket->setStatus($availableTicketStatus);
            $ticket->setPriority($availableTicketPriority);
            $ticket->setCreatedAt((new \DateTime));
            $ticket->setUpdatedAt((new \DateTime));

            $entityManager->persist($ticket);
            $entityManager->flush();

            if (!empty($ticket))
            {
                $thread = new CoreEntities\Thread();
                $thread->setTicket($ticket);
                $thread->setUser($user);
                $thread->setMessage($ticketParameter['ticketParameters']['message']);
                $thread->setCreatedAt(new \DateTime());
                $thread->setUpdatedAt(new \DateTime());
                $thread->setSource('website');
                $thread->setThreadType('create');
                $thread->setCreatedBy('customer');

                $entityManager->persist($thread);
                $entityManager->flush();
            }
        }
    }
}
<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Fixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;

class WelcomeTicket extends DoctrineFixture
{
    private static $seedData = [
        'ticketParameters' => [
            'source'  => 'website',
            'subject' => 'Welcome To UVdesk',
            'message' => '<div style="font-family: sans-serif; line-height:24px;">
                <div style="background-color:#887bf6; height:6px; "></div>
                <a href="https://www.uvdesk.com" target="_blank"><img src="https://s3-ap-southeast-1.amazonaws.com/cdn.uvdesk.com/website/1/201705255926a8fa4ffealogo.png" style="margin:20px 0;" alt="header"></a>
                <h1 style="font-size:18px;">Welcome!</h1>
                <p>Hi community-dev Admin,</p>
                <p>
                Thank you for signing up. It is with great pleasure that I welcome you to the family of uvdesk. Our staff is dedicated to help you out in the best possible way.</p>
                <p>You can help your customers right away by bringing their support queries into UVdesk.</p>
                <p style="margin:50px;"></p>    
                
                
                <p>If you have any query regarding this e-mail then feel free to contact our support team <a href="https://support.uvdesk.com" target="_blank" style="text-decoration:none; color:#7c70f4">support.uvdesk.com</a>.</p>
                <p>You can also directly contact us by sending message on <a href="mailto:support@uvdesk.com" target="_blank" style="text-decoration:none; color:#7c70f4">support@uvdesk.com</a>.</p>
                <p>Thanks!</p>
                
                <p>Regards<br>
                <strong>UVdesk Support Team</strong><br>
                <a href="https://www.uvdesk.com" style="text-decoration:none; color:#887bf6" target="_blank">UVdesk</a></p><p>
                
                <a href="https://www.facebook.com/uvdesk" target="_blank"><img src="https://s3-ap-southeast-1.amazonaws.com/cdn.uvdesk.com/website/1/social_facebook.png"></a>
                <a href="https://twitter.com/uvdesk" target="_blank"><img src="https://s3-ap-southeast-1.amazonaws.com/cdn.uvdesk.com/website/1/social_twitter.png"></a>
                <a href="https://www.linkedin.com/company/uvdesk" target="_blank"><img src="https://s3-ap-southeast-1.amazonaws.com/cdn.uvdesk.com/website/1/social_linked-in.png"></a>
                <a href="https://plus.google.com/+Uvdesk" target="_blank"><img src="https://s3-ap-southeast-1.amazonaws.com/cdn.uvdesk.com/website/1/social_google.png"></a>
            
                </p><div style="background-color:#887bf6;"><a href="https://www.uvdesk.com" target="_blank"><img src="https://s3-ap-southeast-1.amazonaws.com/cdn.uvdesk.com/website/1/201705255926a94c17208uvdesk.png" alt="header"></a></div>
            </div>',
        ],
        'userParameter' => [
            'customerFirstName' =>'UVdesk',
            'customerLastName' =>'Support',
            'customerEmail' => 'support@uvdesk.com',
        ]
    ];
    
    public function load(ObjectManager $entityManager)
    {
        $availableTicketPriority = $entityManager->getRepository('UVDeskCoreFrameworkBundle:TicketPriority')->findOneBy(['code' => 'low']);
        $availableTicketStatus = $entityManager->getRepository('UVDeskCoreFrameworkBundle:TicketStatus')->findOneBy(['code' => 'open']);
        $supportRole = $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportRole')->findOneBy(['code' => 'ROLE_CUSTOMER']);
        
        // Setting user details:
        $user = new CoreEntities\User();
        $user->setEmail(self::$seedData['userParameter']['customerEmail']);
        $user->setFirstName(self::$seedData['userParameter']['customerFirstName']);
        $user->setLastName(self::$seedData['userParameter']['customerLastName']);
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

        // Setting up ticket Data
        $ticket =  new CoreEntities\Ticket();
        $ticket->setSource('website');
        $ticket->setCustomer($user);
        $ticket->setSubject(self::$seedData['ticketParameters']['subject']);
        $ticket->setStatus($availableTicketStatus);
        $ticket->setPriority($availableTicketPriority);
        $ticket->setCreatedAt((new \DateTime));
        $ticket->setUpdatedAt((new \DateTime));
        $entityManager->persist($ticket);
        $entityManager->flush();

        if (!empty($ticket)) {
            $thread = new CoreEntities\Thread();
            $thread->setTicket($ticket);
            $thread->setUser($user);
            $thread->setMessage(self::$seedData['ticketParameters']['message']);
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
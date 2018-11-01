<?php

namespace Webkul\UVDesk\CoreBundle\Fixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Webkul\UVDesk\CoreBundle\Entity as CoreEntities;
use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;
use Webkul\UVDesk\CoreBundle\Templates\Email\Resources as CoreEmailTemplates;

class EmailTemplates extends DoctrineFixture
{
    private static $seeds = [
        CoreEmailTemplates\Agent\TicketReply::class,
        CoreEmailTemplates\Agent\TicketCreated::class,
        CoreEmailTemplates\Agent\AccountCreated::class,
        CoreEmailTemplates\Agent\ForgotPassword::class,
        CoreEmailTemplates\Agent\TicketAssigned::class,
        CoreEmailTemplates\Customer\TicketReply::class,
        CoreEmailTemplates\Customer\TicketCreated::class,
        CoreEmailTemplates\Customer\AccountCreated::class,
        CoreEmailTemplates\Customer\ForgotPassword::class,
    ];

    public function load(ObjectManager $entityManager)
    {
        $emailTemplateCollection = $entityManager->getRepository('UVDeskCoreBundle:EmailTemplates')->findAll();

        if (empty($emailTemplateCollection)) {
            foreach (self::$seeds as $coreEmailTemplate) {
                ($emailTemplate = new CoreEntities\EmailTemplates())
                    ->setName($coreEmailTemplate::getName())
                    ->setSubject($coreEmailTemplate::getSubject())
                    ->setMessage($coreEmailTemplate::getMessage());

                $entityManager->persist($emailTemplate);
            }

            $entityManager->flush();
        }
    }
}

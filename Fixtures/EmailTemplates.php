<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Fixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;
use Webkul\UVDesk\CoreFrameworkBundle\Templates\Email\Resources as CoreEmailTemplates;

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
        $emailTemplateCollection = $entityManager->getRepository('UVDeskCoreFrameworkBundle:EmailTemplates')->findAll();

        if (empty($emailTemplateCollection)) {
            foreach (self::$seeds as $coreEmailTemplate) {
                ($emailTemplate = new CoreEntities\EmailTemplates())
                    ->setName($coreEmailTemplate::getName())
                    ->setTemplateType($coreEmailTemplate::getTemplateType())
                    ->setSubject($coreEmailTemplate::getSubject())
                    ->setMessage($coreEmailTemplate::getMessage());

                $entityManager->persist($emailTemplate);
            }

            $entityManager->flush();
        }
    }
}

<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Fixtures;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Website;

class HelpdeskBranding extends DoctrineFixture
{
    public function load(ObjectManager $entityManager): void
    {
        $website = $entityManager->getRepository(Website::class)->findOneByCode('helpdesk');

        if (empty($website)) {
            ($website = new CoreEntities\Website())
                ->setName('Support Center')
                ->setCode('helpdesk')
                ->setThemeColor('#0056fc')
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());

            $entityManager->persist($website);
            $entityManager->flush();
        }
    }
}

<?php

namespace Webkul\UVDesk\CoreBundle\Fixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Webkul\UVDesk\CoreBundle\Entity as CoreEntities;
use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;

class HelpdeskBranding extends DoctrineFixture
{
    public function load(ObjectManager $entityManager)
    {
        $website = $entityManager->getRepository('UVDeskCoreBundle:Website')->findOneByCode('helpdesk');
        
        if (empty($website)) {
            ($website = new CoreEntities\Website())
                ->setName('Support Center')
                ->setCode('helpdesk')
                ->setLogo('#7E91F0')
                ->setThemeColor('#7E91F0')
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());

            $entityManager->persist($website);
            $entityManager->flush();
        }
    }
}

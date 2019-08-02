<?php

namespace Webkul\UVDesk\CoreBundle\Fixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Webkul\UVDesk\CoreBundle\Entity as CoreEntities;
use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;

class AgentGroups extends DoctrineFixture
{
    private static $seeds = [
        [
            'name' => 'Default',
            'description' => 'Account Owner',
            'isActive' => true,
        ],
    ];

    public function load(ObjectManager $entityManager)
    {
        $availableSupportGroups = $entityManager->getRepository('UVDeskCoreBundle:SupportGroup')->findAll();

        if (empty($availableSupportGroups)) {
            foreach (self::$seeds as $supportGroupSeed) {
                $supportGroup = new CoreEntities\SupportGroup();
                $supportGroup->setName($supportGroupSeed['name']);
                $supportGroup->setDescription($supportGroupSeed['description']);
                $supportGroup->setIsActive($supportGroupSeed['isActive']);
    
                $entityManager->persist($supportGroup);
            }
    
            $entityManager->flush();
        }
    }
}

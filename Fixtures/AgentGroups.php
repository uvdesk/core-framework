<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Fixtures;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportGroup;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;

class AgentGroups extends DoctrineFixture
{
    private static $seeds = [
        [
            'name'        => 'Default',
            'description' => 'Account Owner',
            'isActive'    => true,
        ],
    ];

    public function load(ObjectManager $entityManager): void
    {
        $availableSupportGroups = $entityManager->getRepository(SupportGroup::class)->findAll();

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

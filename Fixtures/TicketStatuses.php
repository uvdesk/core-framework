<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Fixtures;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\TicketStatus;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;

class TicketStatuses extends DoctrineFixture
{
    private static $seeds = [
        [
            'code'        => 'open',
            'description' => 'Open',
            'colorCode'   => '#0056fc',
            'sortOrder'   => 1
        ],
        [
            'code'        => 'pending',
            'description' => 'Pending',
            'colorCode'   => '#FF6A6B',
            'sortOrder'   => 2
        ],
        [
            'code'        => 'answered',
            'description' => 'Answered',
            'colorCode'   => '#FFDE00',
            'sortOrder'   => 3
        ],
        [
            'code'        => 'resolved',
            'description' => 'Resolved',
            'colorCode'   => '#2CD651',
            'sortOrder'   => 4
        ],
        [
            'code'        => 'closed',
            'description' => 'Closed',
            'colorCode'   => '#767676',
            'sortOrder'   => 5
        ],
        [
            'code'        => 'spam',
            'description' => 'Spam',
            'colorCode'   => '#00A1F2',
            'sortOrder'   => 6
        ],
    ];

    public function load(ObjectManager $entityManager): void
    {
        $availableTicketStatuses = $entityManager->getRepository(TicketStatus::class)->findAll();
        $availableTicketStatuses = array_map(function ($ticketStatus) {
            return $ticketStatus->getCode();
        }, $availableTicketStatuses);

        foreach (self::$seeds as $ticketStatusSeed) {
            if (false === in_array($ticketStatusSeed['code'], $availableTicketStatuses)) {
                $ticketStatus = new CoreEntities\TicketStatus();
                $ticketStatus->setCode($ticketStatusSeed['code']);
                $ticketStatus->setDescription($ticketStatusSeed['description']);
                $ticketStatus->setColorCode($ticketStatusSeed['colorCode']);
                $ticketStatus->setSortOrder($ticketStatusSeed['sortOrder']);

                $entityManager->persist($ticketStatus);
            }
        }

        $entityManager->flush();
    }
}

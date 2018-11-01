<?php

namespace Webkul\UVDesk\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Webkul\UVDesk\CoreBundle\Entity as CoreEntities;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ORMLifecycle
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $timestamp = new \DateTime('now');

        switch (true) {
            case $entity instanceof CoreEntities\TicketRating:
                $entity->setCreatedAt($timestamp);
                break;
            case $entity instanceof CoreEntities\Ticket:
            case $entity instanceof CoreEntities\SavedFilters:
                $entity->setCreatedAt($timestamp)->setUpdatedAt($timestamp);
                break;
            case $entity instanceof CoreEntities\Thread:
                $entity->setCreatedAt($timestamp)->setUpdatedAt($timestamp);
                
                if ('reply' === $entity->getThreadType() && 'agent' === $entity->getCreatedBy()) {
                    $ticket = $entity->getTicket();
                    $ticket->setIsNew(false);
                    $ticket->setIsReplied(true);
                    $ticket->setIsCustomerViewed(false);

                    $args->getEntityManager()->persist($ticket);
                } else if ('create' === $entity->getThreadType()) {
                    $ticket = $entity->getTicket();
                    $ticket->setIsReplied(0);

                    $args->getEntityManager()->persist($ticket);
                }
                break;
            default:
                break;
        }

        return;
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $timestamp = new \DateTime('now');

        switch (true) {
            case $entity instanceof CoreEntities\Ticket:
            case $entity instanceof CoreEntities\Thread:
            case $entity instanceof CoreEntities\SavedFilters:
                $entity->setUpdatedAt($timestamp);
                break;
            default:
                break;
        }

        return;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $timestamp = new \DateTime('now');

        switch (true) {
            case $entity instanceof CoreEntities\Mailbox:
                if (true === $entity->getIsLocalized()) {
                    foreach ($this->container->getParameter('uvdesk.mailboxes') as $localizedConfig) {
                        if ($entity->getEmail() === $localizedConfig['email']) {
                            $entity->setHost($localizedConfig['host']);
                            $entity->setPassword($localizedConfig['password']);

                            break;
                        }
                    }
                }
                
                break;
            default:
                break;
        }

        return;
    }
}

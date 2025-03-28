<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Repository;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\AgentActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AgentActivity|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgentActivity|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgentActivity[]    findAll()
 * @method AgentActivity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgentActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentActivity::class);
    }
}

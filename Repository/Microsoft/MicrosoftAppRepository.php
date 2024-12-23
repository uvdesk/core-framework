<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Repository\Microsoft;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft\MicrosoftApp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MicrosoftApp>
 *
 * @method MicrosoftApp|null find($id, $lockMode = null, $lockVersion = null)
 * @method MicrosoftApp|null findOneBy(array $criteria, array $orderBy = null)
 * @method MicrosoftApp[]    findAll()
 * @method MicrosoftApp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MicrosoftAppRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MicrosoftApp::class);
    }

    public function add(MicrosoftApp $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MicrosoftApp $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MicrosoftApp[] Returns an array of MicrosoftApp objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MicrosoftApp
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Repository\Microsoft;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft\MicrosoftAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MicrosoftAccount>
 *
 * @method MicrosoftAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method MicrosoftAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method MicrosoftAccount[]    findAll()
 * @method MicrosoftAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MicrosoftAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MicrosoftAccount::class);
    }

    public function add(MicrosoftAccount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MicrosoftAccount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MicrosoftAccount[] Returns an array of MicrosoftAccount objects
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

//    public function findOneBySomeField($value): ?MicrosoftAccount
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

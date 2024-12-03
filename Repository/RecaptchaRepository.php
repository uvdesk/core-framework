<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Repository;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\Recaptcha;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Recaptcha|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recaptcha|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recaptcha[]    findAll()
 * @method Recaptcha[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecaptchaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recaptcha::class);
    }
}

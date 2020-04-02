<?php

namespace App\Repository;

use App\Entity\Release;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Release|null find($id, $lockMode = null, $lockVersion = null)
 * @method Release|null findOneBy(array $criteria, array $orderBy = null)
 * @method Release[]    findAll()
 * @method Release[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReleaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Release::class);
    }

    public function findLastUpdated(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.updatedAt > :date')
            ->setParameter('date', Carbon::now()->subWeeks(2))
            ->orderBy('r.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

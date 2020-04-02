<?php

namespace App\Repository;

use App\Entity\PackageRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method PackageRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method PackageRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method PackageRequest[]    findAll()
 * @method PackageRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PackageRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PackageRequest::class);
    }

    /**
     * @param array<string> $names
     *
     * @return array<array<string>>
     */
    public function findRequestedPackageNames(array $names): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.name')
            ->where('r.name IN (:names)')
            ->setParameter('names', $names)
            ->getQuery()
            ->getResult();
    }
}

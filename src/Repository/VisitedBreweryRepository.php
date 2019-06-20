<?php

namespace App\Repository;

use App\Entity\VisitedBrewery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method VisitedBrewery|null find($id, $lockMode = null, $lockVersion = null)
 * @method VisitedBrewery|null findOneBy(array $criteria, array $orderBy = null)
 * @method VisitedBrewery[]    findAll()
 * @method VisitedBrewery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VisitedBreweryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, VisitedBrewery::class);
    }

    // /**
    //  * @return VisitedBrewery[] Returns an array of VisitedBrewery objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?VisitedBrewery
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

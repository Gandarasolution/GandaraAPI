<?php

namespace App\Repository;

use App\Entity\PlanningEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlanningEvenement>
 */
class PlanningEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningEvenement::class);
    }

    /**
     * @return PlanningEvenement[] Returns an array of PlanningEvenement objects
     */
    public function findEventsByDate(\DateTimeInterface $dateStart, \DateTimeInterface $dateEnd ): array
    {
        $startOfDay = (clone $dateStart)->setTime(0, 0, 0);
        $endOfDay = (clone $dateEnd)->setTime(23, 59, 59);

        return $this->createQueryBuilder('p')
            ->andWhere('p.debutplanningevenement <= :endOfDay')
            ->andWhere('p.finplanningevenement >= :startOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return PlanningEvenement[] Returns an array of PlanningEvenement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PlanningEvenement
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

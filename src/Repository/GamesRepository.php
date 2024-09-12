<?php

namespace App\Repository;

use App\Entity\Games;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Games>
 */
class GamesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Games::class);
    }

    public function findByAllQueries(?string $uuid): array
    {
        $qb = $this->createQueryBuilder('g');

        if ($name !== null) {
            $qb->andWhere('g.uuid = :uuid')
                ->setParameter('uuid', $uuid);
        }

        return $qb->getQuery()->getResult();
    }
}

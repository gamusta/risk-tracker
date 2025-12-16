<?php

declare(strict_types=1);

namespace App\Risk\Infrastructure\Persistence;

use App\Risk\Domain\Entity\RiskHistory;
use App\Risk\Domain\Repository\RiskHistoryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository: Doctrine implementation RiskHistory
 */
class DoctrineRiskHistoryRepository extends ServiceEntityRepository implements RiskHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RiskHistory::class);
    }

    public function save(RiskHistory $history): void
    {
        $this->getEntityManager()->persist($history);
        $this->getEntityManager()->flush();
    }

    public function findByRiskId(int $riskId): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.riskId = :riskId')
            ->setParameter('riskId', $riskId)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

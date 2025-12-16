<?php

declare(strict_types=1);

namespace App\Risk\Infrastructure\Persistence;

use App\Risk\Domain\Entity\Risk;
use App\Risk\Domain\Repository\RiskRepositoryInterface;
use App\Risk\Domain\ValueObject\RiskStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Infrastructure: Implémentation Doctrine du repository
 *
 * @extends ServiceEntityRepository<Risk>
 */
class DoctrineRiskRepository extends ServiceEntityRepository implements RiskRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Risk::class);
    }

    public function save(Risk $risk): void
    {
        $this->getEntityManager()->persist($risk);
        $this->getEntityManager()->flush();
    }

    public function delete(Risk $risk): void
    {
        $this->getEntityManager()->remove($risk);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?Risk
    {
        return $this->find($id);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }

    public function findByStatus(RiskStatus $status): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySite(int $siteId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.siteId = :siteId')
            ->setParameter('siteId', $siteId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCriticalRisks(): array
    {
        // Score >= 20 (5×4 ou 5×5)
        return $this->createQueryBuilder('r')
            ->where('r.score >= 20')
            ->orderBy('r.score', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

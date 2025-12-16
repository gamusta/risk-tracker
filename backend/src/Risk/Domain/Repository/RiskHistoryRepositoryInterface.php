<?php

declare(strict_types=1);

namespace App\Risk\Domain\Repository;

use App\Risk\Domain\Entity\RiskHistory;

/**
 * Interface: Repository RiskHistory (Domain)
 */
interface RiskHistoryRepositoryInterface
{
    public function save(RiskHistory $history): void;

    public function findByRiskId(int $riskId): array;

    /** @return RiskHistory[] */
    public function findAll(): array;
}

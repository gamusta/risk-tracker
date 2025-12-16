<?php

declare(strict_types=1);

namespace App\Risk\Domain\Repository;

use App\Risk\Domain\Entity\Risk;
use App\Risk\Domain\ValueObject\RiskStatus;

/**
 * Interface Domain: Contrat repository (DIP)
 */
interface RiskRepositoryInterface
{
    public function save(Risk $risk): void;

    public function delete(Risk $risk): void;

    public function findById(int $id): ?Risk;

    /**
     * @return Risk[]
     */
    public function findAll(): array;

    /**
     * @return Risk[]
     */
    public function findByStatus(RiskStatus $status): array;

    /**
     * @return Risk[]
     */
    public function findBySite(int $siteId): array;

    /**
     * @return Risk[]
     */
    public function findCriticalRisks(): array;
}

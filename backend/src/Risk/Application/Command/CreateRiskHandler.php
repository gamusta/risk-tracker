<?php

declare(strict_types=1);

namespace App\Risk\Application\Command;

use App\Risk\Domain\Entity\Risk;
use App\Risk\Domain\Repository\RiskRepositoryInterface;
use App\Risk\Domain\ValueObject\Probability;
use App\Risk\Domain\ValueObject\Severity;

/**
 * Handler: Use case création risque
 */
final readonly class CreateRiskHandler
{
    public function __construct(
        private RiskRepositoryInterface $riskRepository
    ) {
    }

    public function __invoke(CreateRiskCommand $command): Risk
    {
        $risk = Risk::create(
            title: $command->title,
            type: $command->type,
            severity: Severity::fromInt($command->severity),
            probability: Probability::fromInt($command->probability),
            description: $command->description
        );

        if ($command->siteId) {
            $risk->assignToSite($command->siteId);
        }

        if ($command->assignedToId) {
            $risk->assignToUser($command->assignedToId);
        }

        $this->riskRepository->save($risk);

        return $risk;
    }
}

<?php

declare(strict_types=1);

namespace App\Risk\Application\Command;

use App\Risk\Domain\Entity\Risk;
use App\Risk\Domain\Repository\RiskRepositoryInterface;
use App\Risk\Domain\ValueObject\Severity;
use App\Risk\Domain\ValueObject\Probability;
use InvalidArgumentException;

/**
 * Handler: Use case update risque
 */
final readonly class UpdateRiskHandler
{
    public function __construct(
        private RiskRepositoryInterface $riskRepository
    ) {
    }

    public function __invoke(UpdateRiskCommand $command): Risk
    {
        $risk = $this->riskRepository->findById($command->id);

        if (!$risk) {
            throw new InvalidArgumentException(sprintf('Risk with ID %d not found', $command->id));
        }

        $risk->update(
            title: $command->title,
            type: $command->type,
            description: $command->description
        );

        // Si severity/probability fournis, réassessment
        if ($command->severity !== null && $command->probability !== null) {
            $risk->assess(
                Severity::fromInt($command->severity),
                Probability::fromInt($command->probability)
            );
        }

        $this->riskRepository->save($risk);

        return $risk;
    }
}

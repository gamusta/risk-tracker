<?php

declare(strict_types=1);

namespace App\Risk\Application\Command;

use App\Risk\Domain\Repository\RiskRepositoryInterface;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Risk\Domain\Event\RiskStatusChanged;

/**
 * Handler: Use case changement statut
 */
final readonly class ChangeRiskStatusHandler
{
    public function __construct(
        private RiskRepositoryInterface $riskRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(ChangeRiskStatusCommand $command): void
    {
        $risk = $this->riskRepository->findById($command->riskId);

        if (!$risk) {
            throw new InvalidArgumentException(
                sprintf('Risk with ID %d not found', $command->riskId)
            );
        }

        $oldStatus = $risk->getStatus();

        // Comportement métier: validation transition
        $risk->changeStatus($command->newStatus);

        $this->riskRepository->save($risk);

        // Event Observer Pattern
        $riskId = $risk->getId();
        if ($riskId === null) {
            throw new \RuntimeException('Risk ID cannot be null after save');
        }

        $event = new RiskStatusChanged(
            riskId: $riskId,
            oldStatus: $oldStatus,
            newStatus: $command->newStatus,
            occurredAt: new \DateTimeImmutable()
        );

        $this->eventDispatcher->dispatch($event);
    }
}

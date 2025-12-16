<?php

declare(strict_types=1);

namespace App\Risk\Infrastructure\EventSubscriber;

use App\Risk\Domain\Entity\RiskHistory;
use App\Risk\Domain\Event\RiskStatusChanged;
use App\Risk\Domain\Repository\RiskHistoryRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventSubscriber: Observer Pattern - Log transitions statut
 */
final readonly class RiskStatusChangedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private RiskHistoryRepositoryInterface $historyRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RiskStatusChanged::class => 'onRiskStatusChanged',
        ];
    }

    public function onRiskStatusChanged(RiskStatusChanged $event): void
    {
        $this->logger->info('Risk status changed', [
            'risk_id' => $event->riskId,
            'old_status' => $event->oldStatus->value,
            'new_status' => $event->newStatus->value,
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s'),
        ]);

        // Enregistrer dans l'historique (Pattern: Memento)
        $history = RiskHistory::record(
            riskId: $event->riskId,
            action: 'status_changed',
            changes: [
                'old_status' => $event->oldStatus->value,
                'new_status' => $event->newStatus->value,
            ],
            userId: null  // TODO: récupérer depuis Security context
        );

        $this->historyRepository->save($history);

        // Potentiellement:
        // - Envoyer notification email
        // - Déclencher webhook
        // - Envoyer message Slack
    }
}

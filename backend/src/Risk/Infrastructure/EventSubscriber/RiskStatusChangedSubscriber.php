<?php

declare(strict_types=1);

namespace App\Risk\Infrastructure\EventSubscriber;

use App\Risk\Domain\Event\RiskStatusChanged;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventSubscriber: Observer Pattern - Log transitions statut
 */
final readonly class RiskStatusChangedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
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

        // Ici on pourrait:
        // - Créer RiskHistory
        // - Envoyer notification email
        // - Déclencher webhook
        // - Envoyer message Slack
    }
}

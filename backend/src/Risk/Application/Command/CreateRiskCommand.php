<?php

declare(strict_types=1);

namespace App\Risk\Application\Command;

/**
 * Command: Créer risque
 */
final readonly class CreateRiskCommand
{
    public function __construct(
        public string $title,
        public string $type,
        public int $severity,
        public int $probability,
        public ?string $description = null,
        public ?int $siteId = null,
        public ?int $assignedToId = null
    ) {
    }
}

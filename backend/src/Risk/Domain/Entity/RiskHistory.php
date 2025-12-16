<?php

declare(strict_types=1);

namespace App\Risk\Domain\Entity;

use App\Risk\Infrastructure\Persistence\DoctrineRiskHistoryRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entity: Historique modifications risque (Audit Trail)
 *
 * Pattern: Memento - Capture état risque à chaque modification
 */
#[ORM\Entity(repositoryClass: DoctrineRiskHistoryRepository::class)]
#[ORM\Table(name: 'risk_history')]
class RiskHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $riskId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $action;  // created, updated, status_changed, assessed, closed

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $changes = null;  // Old/new values

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $userId = null;  // Qui a fait la modif

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed>|null $changes
     */
    private function __construct(
        int $riskId,
        string $action,
        ?array $changes = null,
        ?int $userId = null
    ) {
        $this->riskId = $riskId;
        $this->action = $action;
        $this->changes = $changes;
        $this->userId = $userId;
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * @param array<string, mixed>|null $changes
     */
    public static function record(
        int $riskId,
        string $action,
        ?array $changes = null,
        ?int $userId = null
    ): self {
        return new self($riskId, $action, $changes, $userId);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRiskId(): int
    {
        return $this->riskId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getChanges(): ?array
    {
        return $this->changes;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

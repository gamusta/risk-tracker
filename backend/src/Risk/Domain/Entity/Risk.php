<?php

declare(strict_types=1);

namespace App\Risk\Domain\Entity;

use App\Risk\Domain\ValueObject\Probability;
use App\Risk\Domain\ValueObject\RiskScore;
use App\Risk\Domain\ValueObject\RiskStatus;
use App\Risk\Domain\ValueObject\Severity;
use DateTimeImmutable;
use InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Domain: Risk (comportements métier)
 * ValueObjects stockés en primitives pour simplicité Doctrine
 */
class Risk
{
    private ?int $id = null;
    private string $title;
    private ?string $description = null;
    private string $type;

    private int $severity;
    private int $probability;
    private string $status;
    private ?int $score = null;
    private ?int $siteId = null;
    private ?int $assignedToId = null;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    private function __construct(
        string $title,
        string $type,
        Severity $severity,
        Probability $probability,
        ?string $description = null
    ) {
        $this->setTitle($title);
        $this->setType($type);
        $this->severity = $severity->value();
        $this->probability = $probability->value();
        $this->description = $description;
        $this->status = RiskStatus::DRAFT->value;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->calculateScore();
    }

    public static function create(
        string $title,
        string $type,
        Severity $severity,
        Probability $probability,
        ?string $description = null
    ): self {
        return new self($title, $type, $severity, $probability, $description);
    }

    /**
     * Comportement métier: Transition statut
     */
    public function changeStatus(RiskStatus $newStatus): void
    {
        $currentStatus = RiskStatus::from($this->status);

        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                sprintf('Cannot transition from %s to %s', $currentStatus->value, $newStatus->value)
            );
        }

        $this->status = $newStatus->value;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Comportement métier: Mise à jour évaluation
     */
    public function assess(Severity $severity, Probability $probability): void
    {
        $this->severity = $severity->value();
        $this->probability = $probability->value();
        $this->calculateScore();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Comportement métier: Calcul score automatique
     */
    private function calculateScore(): void
    {
        $score = RiskScore::calculate(
            Severity::fromInt($this->severity),
            Probability::fromInt($this->probability)
        );
        $this->score = $score->value();
    }

    /**
     * Comportement métier: Assigner à un site
     */
    public function assignToSite(int $siteId): void
    {
        $this->siteId = $siteId;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Comportement métier: Assigner à un utilisateur
     */
    public function assignToUser(int $userId): void
    {
        $this->assignedToId = $userId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function close(): void
    {
        $this->changeStatus(RiskStatus::CLOSED);
    }

    // Getters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSeverity(): Severity
    {
        return Severity::fromInt($this->severity);
    }

    public function getProbability(): Probability
    {
        return Probability::fromInt($this->probability);
    }

    public function getStatus(): RiskStatus
    {
        return RiskStatus::from($this->status);
    }

    public function getScore(): ?RiskScore
    {
        return $this->score ? RiskScore::fromInt($this->score) : null;
    }

    public function getSiteId(): ?int
    {
        return $this->siteId;
    }

    public function getAssignedToId(): ?int
    {
        return $this->assignedToId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Setters avec validation

    private function setTitle(string $title): void
    {
        if (strlen(trim($title)) < 3) {
            throw new InvalidArgumentException('Title must be at least 3 characters');
        }

        $this->title = $title;
    }

    private function setType(string $type): void
    {
        $allowedTypes = ['security', 'environment', 'social', 'cyber'];

        if (!in_array($type, $allowedTypes, true)) {
            throw new InvalidArgumentException(
                sprintf('Type must be one of: %s', implode(', ', $allowedTypes))
            );
        }

        $this->type = $type;
    }

    public function update(string $title, string $type, ?string $description): void
    {
        $this->setTitle($title);
        $this->setType($type);
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
    }
}
<?php

declare(strict_types=1);

namespace App\Risk\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Risk\Infrastructure\ApiPlatform\State\RiskProvider;
use App\Risk\Infrastructure\ApiPlatform\State\RiskProcessor;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * API Resource: DTO exposition Risk via API Platform
 */
#[ApiResource(
    shortName: 'Risk',
    operations: [
        new GetCollection(),
        new Get(),
        new Post(
            processor: RiskProcessor::class
        ),
        new Put(
            processor: RiskProcessor::class
        ),
        new Delete(
            processor: RiskProcessor::class
        ),
    ],
    normalizationContext: ['groups' => ['risk:read']],
    denormalizationContext: ['groups' => ['risk:write']],
    provider: RiskProvider::class
)]
class RiskResource
{
    #[Groups(['risk:read'])]
    public ?int $id = null;

    #[Groups(['risk:read', 'risk:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $title;

    #[Groups(['risk:read', 'risk:write'])]
    public ?string $description = null;

    #[Groups(['risk:read', 'risk:write'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['security', 'environment', 'social', 'cyber'])]
    public string $type;

    #[Groups(['risk:read', 'risk:write'])]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5)]
    public int $severity;

    #[Groups(['risk:read', 'risk:write'])]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5)]
    public int $probability;

    #[Groups(['risk:read'])]
    public string $status;

    #[Groups(['risk:read'])]
    public ?int $score = null;

    #[Groups(['risk:read'])]
    public ?string $scoreLevel = null;

    #[Groups(['risk:read', 'risk:write'])]
    public ?int $siteId = null;

    #[Groups(['risk:read', 'risk:write'])]
    public ?int $assignedToId = null;

    #[Groups(['risk:read'])]
    public ?\DateTimeImmutable $createdAt = null;

    #[Groups(['risk:read'])]
    public ?\DateTimeImmutable $updatedAt = null;
}

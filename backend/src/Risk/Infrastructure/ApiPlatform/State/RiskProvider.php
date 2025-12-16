<?php

declare(strict_types=1);

namespace App\Risk\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Risk\Domain\Entity\Risk;
use App\Risk\Domain\Repository\RiskRepositoryInterface;
use App\Risk\Infrastructure\ApiPlatform\RiskResource;

/**
 * State Provider: Transform Domain Entity → API Resource
 * @implements ProviderInterface<RiskResource>
 */
final readonly class RiskProvider implements ProviderInterface
{
    public function __construct(
        private RiskRepositoryInterface $riskRepository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // GetCollection
        if (empty($uriVariables)) {
            $risks = $this->riskRepository->findAll();
            return array_map(fn(Risk $risk) => $this->transformToResource($risk), $risks);
        }

        // Get single
        $risk = $this->riskRepository->findById($uriVariables['id']);

        if (!$risk) {
            return null;
        }

        return $this->transformToResource($risk);
    }

    private function transformToResource(Risk $risk): RiskResource
    {
        $resource = new RiskResource();
        $resource->id = $risk->getId();
        $resource->title = $risk->getTitle();
        $resource->description = $risk->getDescription();
        $resource->type = $risk->getType();
        $resource->severity = $risk->getSeverity()->value();
        $resource->probability = $risk->getProbability()->value();
        $resource->status = $risk->getStatus()->value;
        $resource->score = $risk->getScore()?->value();
        $resource->scoreLevel = $risk->getScore()?->level();
        $resource->siteId = $risk->getSiteId();
        $resource->assignedToId = $risk->getAssignedToId();
        $resource->createdAt = $risk->getCreatedAt();
        $resource->updatedAt = $risk->getUpdatedAt();

        return $resource;
    }
}

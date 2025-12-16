<?php

declare(strict_types=1);

namespace App\Risk\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Risk\Application\Command\CreateRiskCommand;
use App\Risk\Application\Command\CreateRiskHandler;
use App\Risk\Application\Command\UpdateRiskCommand;
use App\Risk\Application\Command\UpdateRiskHandler;
use App\Risk\Domain\Repository\RiskRepositoryInterface;
use App\Risk\Infrastructure\ApiPlatform\RiskResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * State Processor: Handle POST/PUT/DELETE via Handlers
 */
final readonly class RiskProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateRiskHandler $createRiskHandler,
        private UpdateRiskHandler $updateRiskHandler,
        private RiskRepositoryInterface $riskRepository,
        private RiskProvider $riskProvider
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?RiskResource
    {
        assert($data instanceof RiskResource);

        // DELETE
        if ($operation instanceof \ApiPlatform\Metadata\Delete) {
            $risk = $this->riskRepository->findById($uriVariables['id']);
            if (!$risk) {
                throw new NotFoundHttpException('Risk not found');
            }
            $this->riskRepository->delete($risk);
            return null;
        }

        // POST
        if ($operation instanceof \ApiPlatform\Metadata\Post) {
            $command = new CreateRiskCommand(
                title: $data->title,
                type: $data->type,
                severity: $data->severity,
                probability: $data->probability,
                description: $data->description,
                siteId: $data->siteId,
                assignedToId: $data->assignedToId
            );

            $risk = ($this->createRiskHandler)($command);
            return $this->riskProvider->provide($operation, ['id' => $risk->getId()], $context);
        }

        // PUT
        if ($operation instanceof \ApiPlatform\Metadata\Put) {
            $command = new UpdateRiskCommand(
                id: $uriVariables['id'],
                title: $data->title,
                type: $data->type,
                description: $data->description
            );

            $risk = ($this->updateRiskHandler)($command);
            return $this->riskProvider->provide($operation, ['id' => $risk->getId()], $context);
        }

        return null;
    }
}

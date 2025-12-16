# Risk Feature - Architecture DDD & Design Patterns

## Vue d'ensemble

Feature **Risk** implémentée selon **Domain-Driven Design (DDD)** avec structure **Feature-Based**.

```
src/Risk/
├── Domain/              # Logique métier pure (framework-agnostic)
├── Application/         # Use cases & orchestration
└── Infrastructure/      # Implémentations techniques (Doctrine, API Platform)
```

---

## Domain-Driven Design (DDD)

### Principes DDD appliqués

**1. Ubiquitous Language**
- Terminologie métier dans code: Risk, Severity, Probability, Status
- Enum `RiskStatus` reflète workflow métier exact
- Méthodes métier: `assess()`, `changeStatus()`, `close()`

**2. Domain Layer isolé**
- Aucune dépendance framework dans `Domain/`
- ValueObjects encapsulent règles métier
- Entity `Risk` contient comportements (pas juste getters/setters)

**3. Bounded Context**
- Feature `Risk` = contexte délimité
- Communication via Events (`RiskStatusChanged`)
- Interfaces repository pour isolation

---

## Structure par couches (Layered Architecture)

### Domain Layer

**Responsabilité**: Logique métier pure, règles business

**Contenu**:
```
Domain/
├── Entity/
│   └── Risk.php                    # Entité riche avec comportements
├── ValueObject/
│   ├── RiskStatus.php              # Enum workflow + transitions (State Pattern)
│   ├── Severity.php                # Validation 1-5
│   ├── Probability.php             # Validation 1-5
│   └── RiskScore.php               # Calcul score (Strategy Pattern)
├── Repository/
│   └── RiskRepositoryInterface.php # Contrat (Dependency Inversion)
├── Service/
│   └── ScoreCalculatorInterface.php # Stratégie calcul
└── Event/
    └── RiskStatusChanged.php       # Domain Event (Observer Pattern)
```

**Caractéristiques**:
- ✅ Aucun `use Doctrine\...` ou `use Symfony\...`
- ✅ Règles métier centralisées
- ✅ Testable sans framework

**Exemple comportement métier**:
```php
// Risk.php - Entité riche DDD
public function changeStatus(RiskStatus $newStatus): void
{
    $currentStatus = RiskStatus::from($this->status);

    // Validation métier via State Pattern
    if (!$currentStatus->canTransitionTo($newStatus)) {
        throw new InvalidArgumentException(
            sprintf('Cannot transition from %s to %s', $currentStatus->value, $newStatus->value)
        );
    }

    $this->status = $newStatus->value;
    $this->updatedAt = new DateTimeImmutable();
}
```

---

### Application Layer

**Responsabilité**: Orchestration use cases, coordination

**Contenu**:
```
Application/
└── Command/
    ├── CreateRiskCommand.php       # DTO use case
    ├── CreateRiskHandler.php       # Orchestration
    ├── UpdateRiskCommand.php
    ├── UpdateRiskHandler.php
    ├── ChangeRiskStatusCommand.php
    └── ChangeRiskStatusHandler.php # Dispatch events
```

**Caractéristiques**:
- ✅ Commands = intentions utilisateur (CQRS pattern)
- ✅ Handlers = use cases orchestrés
- ✅ Dispatch Domain Events
- ✅ Dépend uniquement du Domain

**Exemple Handler**:
```php
public function __invoke(ChangeRiskStatusCommand $command): void
{
    $risk = $this->riskRepository->findById($command->riskId);
    $oldStatus = $risk->getStatus();

    // Comportement Domain
    $risk->changeStatus($command->newStatus);

    $this->riskRepository->save($risk);

    // Observer Pattern
    $this->eventDispatcher->dispatch(
        new RiskStatusChanged($risk->getId(), $oldStatus, $command->newStatus)
    );
}
```

---

### Infrastructure Layer

**Responsabilité**: Détails techniques, frameworks

**Contenu**:
```
Infrastructure/
├── Persistence/
│   └── DoctrineRiskRepository.php  # Implémente RiskRepositoryInterface
├── ApiPlatform/
│   ├── RiskResource.php            # DTO API
│   └── State/
│       ├── RiskProvider.php        # Transformation Domain → API
│       └── RiskProcessor.php       # Transformation API → Domain
├── EventSubscriber/
│   └── RiskStatusChangedSubscriber.php # Observer Pattern
└── Service/
    ├── SimpleScoreCalculator.php   # Strategy Pattern
    ├── MatrixScoreCalculator.php
    └── AdvancedScoreCalculator.php
```

**Caractéristiques**:
- ✅ Implémente interfaces Domain
- ✅ Doctrine ORM mapping (attributes)
- ✅ API Platform exposition
- ✅ Event Symfony integration

---

## Design Patterns implémentés

### 1. Repository Pattern

**Intent**: Abstraction accès données

**Implémentation**:
```php
// Domain/Repository/RiskRepositoryInterface.php (contrat)
interface RiskRepositoryInterface
{
    public function save(Risk $risk): void;
    public function findById(int $id): ?Risk;
    public function findByStatus(RiskStatus $status): array;
}

// Infrastructure/Persistence/DoctrineRiskRepository.php (implémentation)
class DoctrineRiskRepository extends ServiceEntityRepository implements RiskRepositoryInterface
{
    // Détails Doctrine cachés du Domain
}
```

**Avantages**:
- Domain ne connaît pas Doctrine
- Tests facilités (mock interface)
- Changement ORM sans impact Domain

---

### 2. State Pattern

**Intent**: Workflow statuts avec transitions contrôlées

**Implémentation**:
```php
// Domain/ValueObject/RiskStatus.php
enum RiskStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case ASSESSED = 'assessed';
    case MITIGATED = 'mitigated';
    case CLOSED = 'closed';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::OPEN],
            self::OPEN => [self::ASSESSED, self::CLOSED],
            self::ASSESSED => [self::MITIGATED, self::CLOSED],
            self::MITIGATED => [self::CLOSED],
            self::CLOSED => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }
}
```

**Workflow**:
```
draft → open → assessed → mitigated → closed
         └──────────────────────────→ closed
```

**Avantages**:
- Règles workflow centralisées
- Impossible transition invalide
- Facile ajouter nouveaux états

---

### 3. Strategy Pattern

**Intent**: Algorithmes calcul score interchangeables

**Implémentation**:
```php
// Domain/Service/ScoreCalculatorInterface.php
interface ScoreCalculatorInterface
{
    public function calculate(Severity $severity, Probability $probability): RiskScore;
}

// Infrastructure/Service/SimpleScoreCalculator.php
class SimpleScoreCalculator implements ScoreCalculatorInterface
{
    public function calculate(Severity $severity, Probability $probability): RiskScore
    {
        return RiskScore::fromInt($severity->value() * $probability->value());
    }
}

// Infrastructure/Service/MatrixScoreCalculator.php
class MatrixScoreCalculator implements ScoreCalculatorInterface
{
    public function calculate(Severity $severity, Probability $probability): RiskScore
    {
        // Matrice 5x5 avec poids personnalisés
        $matrix = [
            [1, 2, 3, 4, 5],
            [2, 4, 6, 8, 10],
            [3, 6, 9, 12, 15],
            [4, 8, 12, 16, 20],
            [5, 10, 15, 20, 25]
        ];
        return RiskScore::fromInt($matrix[$severity->value()-1][$probability->value()-1]);
    }
}
```

**Configuration** (services.yaml):
```yaml
# Changement stratégie via config
App\Risk\Domain\Service\ScoreCalculatorInterface:
    class: App\Risk\Infrastructure\Service\MatrixScoreCalculator
```

**Avantages**:
- Changement algorithme sans toucher Domain
- Tests unitaires par stratégie
- Ajout nouvelles stratégies facile

---

### 4. Observer Pattern (via Symfony Events)

**Intent**: Notification changements sans couplage

**Implémentation**:
```php
// Domain/Event/RiskStatusChanged.php (Domain Event)
final readonly class RiskStatusChanged
{
    public function __construct(
        public int $riskId,
        public RiskStatus $oldStatus,
        public RiskStatus $newStatus,
        public DateTimeImmutable $occurredAt
    ) {}
}

// Application/Command/ChangeRiskStatusHandler.php (Publisher)
public function __invoke(ChangeRiskStatusCommand $command): void
{
    // ... changement status ...

    $event = new RiskStatusChanged($riskId, $oldStatus, $newStatus, new DateTimeImmutable());
    $this->eventDispatcher->dispatch($event);
}

// Infrastructure/EventSubscriber/RiskStatusChangedSubscriber.php (Observer)
class RiskStatusChangedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [RiskStatusChanged::class => 'onRiskStatusChanged'];
    }

    public function onRiskStatusChanged(RiskStatusChanged $event): void
    {
        $this->logger->info('Risk status changed', [...]);
        // Peut déclencher: email, webhook, historique, notification...
    }
}
```

**Avantages**:
- Domain ignorant des observateurs
- Ajout observateurs sans modifier émetteur
- Découplage total

**Extensibilité**:
```php
// Facile ajouter nouveaux observateurs
class RiskHistorySubscriber implements EventSubscriberInterface {
    public function onRiskStatusChanged(RiskStatusChanged $event): void {
        // Créer historique en DB
    }
}

class SlackNotificationSubscriber implements EventSubscriberInterface {
    public function onRiskStatusChanged(RiskStatusChanged $event): void {
        // Envoyer notif Slack
    }
}
```

---

### 5. Command Pattern (CQRS)

**Intent**: Séparer intentions (Commands) et exécution (Handlers)

**Implémentation**:
```php
// Application/Command/CreateRiskCommand.php (Command)
final readonly class CreateRiskCommand
{
    public function __construct(
        public string $title,
        public string $type,
        public int $severity,
        public int $probability,
        public ?string $description = null
    ) {}
}

// Application/Command/CreateRiskHandler.php (Handler)
final readonly class CreateRiskHandler
{
    public function __invoke(CreateRiskCommand $command): Risk
    {
        $risk = Risk::create(
            title: $command->title,
            type: $command->type,
            severity: Severity::fromInt($command->severity),
            probability: Probability::fromInt($command->probability)
        );

        $this->riskRepository->save($risk);
        return $risk;
    }
}
```

**Avantages**:
- Intentions explicites
- Handlers testables isolément
- Facilite audit/logging

---

### 6. Value Object Pattern

**Intent**: Objets immuables encapsulant règles métier

**Implémentation**:
```php
// Domain/ValueObject/Severity.php
final class Severity
{
    private const MIN_VALUE = 1;
    private const MAX_VALUE = 5;

    #[ORM\Column(type: 'integer', name: 'severity')]
    private int $value;

    public function __construct(?int $value = null)
    {
        if ($value === null) return; // Doctrine hydration

        if ($value < self::MIN_VALUE || $value > self::MAX_VALUE) {
            throw new InvalidArgumentException(
                sprintf('Severity must be between %d and %d, got %d',
                    self::MIN_VALUE, self::MAX_VALUE, $value)
            );
        }
        $this->value = $value;
    }

    public static function fromInt(int $value): self {
        return new self($value);
    }

    public function value(): int {
        return $this->value;
    }

    public function equals(self $other): bool {
        return $this->value === $other->value;
    }
}
```

**Avantages**:
- Validation centralisée
- Impossible état invalide
- Type-safety PHP

---

### 7. Factory Method Pattern

**Intent**: Création entités complexes

**Implémentation**:
```php
// Domain/Entity/Risk.php
class Risk
{
    private function __construct(...) { /* ... */ }

    // Factory method
    public static function create(
        string $title,
        string $type,
        Severity $severity,
        Probability $probability,
        ?string $description = null
    ): self {
        $risk = new self($title, $type, $severity, $probability, $description);
        // Initialisation complexe...
        $risk->calculateScore();
        return $risk;
    }
}
```

**Avantages**:
- Constructeur privé = création contrôlée
- Named constructor = intent clair
- Initialisation garantie

---

## Dependency Inversion Principle (SOLID)

**Configuration** (config/services.yaml):
```yaml
# Bind interfaces Domain → implémentations Infrastructure
App\Risk\Domain\Repository\RiskRepositoryInterface:
    class: App\Risk\Infrastructure\Persistence\DoctrineRiskRepository

App\Risk\Domain\Service\ScoreCalculatorInterface:
    class: App\Risk\Infrastructure\Service\SimpleScoreCalculator
```

**Flux dépendances**:
```
Infrastructure → Application → Domain
     ↓              ↓            ↑
(implémente)   (utilise)   (définit contrats)
```

---

## Mapping Doctrine (ORM)

**Choix**: Attributes PHP 8 (moderne)

**Entité Domain avec mapping**:
```php
#[ORM\Entity(repositoryClass: DoctrineRiskRepository::class)]
#[ORM\Table(name: 'risks')]
class Risk
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $severity; // ValueObject → primitive pour Doctrine

    #[ORM\Column(type: 'string', length: 50)]
    private string $status; // Enum → string

    // Getters retournent ValueObjects
    public function getSeverity(): Severity {
        return Severity::fromInt($this->severity);
    }
}
```

**Trade-off DDD**:
- ❌ ValueObjects pas directement embeddables (complexité Doctrine)
- ✅ Stockage primitives + reconstruction ValueObjects via getters
- ✅ Domain reste pur (comportements métier préservés)

---

## API Platform integration

**Séparation Domain ↔ API**:

```php
// Infrastructure/ApiPlatform/RiskResource.php (DTO API)
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(processor: RiskProcessor::class),
        new Patch(uriTemplate: '/risks/{id}/status', processor: RiskProcessor::class)
    ]
)]
class RiskResource {
    public string $title;
    public int $severity;
    public string $status;
}

// Infrastructure/ApiPlatform/State/RiskProvider.php
// Transformation: Domain Entity → API Resource
private function transformToResource(Risk $risk): RiskResource
{
    $resource = new RiskResource();
    $resource->title = $risk->getTitle();
    $resource->severity = $risk->getSeverity()->value(); // ValueObject → int
    $resource->status = $risk->getStatus()->value; // Enum → string
    return $resource;
}

// Infrastructure/ApiPlatform/State/RiskProcessor.php
// Transformation: API Resource → Domain Command
if ($operation instanceof Post) {
    $command = new CreateRiskCommand(
        title: $data->title,
        severity: $data->severity, // int → ValueObject via Handler
        probability: $data->probability
    );
    return ($this->createRiskHandler)($command);
}
```

**Avantages**:
- API découplée du Domain
- Évolution indépendante
- Validation API ≠ Validation Domain

---

## Tests (structure)

```
tests/
├── Unit/                           # Tests Domain (pur PHP)
│   ├── Risk/Domain/ValueObject/
│   │   ├── SeverityTest.php       # Validation 1-5
│   │   ├── RiskStatusTest.php     # Transitions State Pattern
│   │   └── RiskScoreTest.php      # Calculs
│   └── Risk/Domain/Entity/
│       └── RiskTest.php            # Comportements métier
│
├── Integration/                    # Tests Infrastructure
│   └── Risk/Infrastructure/
│       └── DoctrineRiskRepositoryTest.php
│
└── Functional/                     # Tests E2E API
    └── Risk/
        └── RiskWorkflowTest.php    # Workflow complet via API
```

**Exemple test unitaire Domain**:
```php
class RiskStatusTest extends TestCase
{
    public function test_can_transition_from_draft_to_open(): void
    {
        $status = RiskStatus::DRAFT;
        $this->assertTrue($status->canTransitionTo(RiskStatus::OPEN));
    }

    public function test_cannot_transition_from_open_to_mitigated(): void
    {
        $status = RiskStatus::OPEN;
        $this->assertFalse($status->canTransitionTo(RiskStatus::MITIGATED));
    }
}
```

---

## Avantages architecture

**Maintenabilité**:
- ✅ Logique métier isolée et testable
- ✅ Changement framework sans impacter Domain
- ✅ Patterns explicites = code autodocumenté

**Scalabilité**:
- ✅ Ajout features facile (nouvelle feature = nouveau dossier)
- ✅ Ajout observateurs sans toucher code existant
- ✅ Changement stratégies via config

**Testabilité**:
- ✅ Domain testable sans DB/framework
- ✅ Interfaces mockables
- ✅ Comportements isolés

**Collaboration**:
- ✅ Structure claire = onboarding rapide
- ✅ Ubiquitous Language = communication métier
- ✅ Bounded Context = équipes autonomes possibles

---

## Évolutions possibles

**Patterns additionnels**:
- **Specification Pattern**: Critères recherche complexes
- **Chain of Responsibility**: Validations enchainées
- **Builder Pattern**: Construction rapports complexes
- **Memento Pattern**: Historique + undo/redo

**CQRS avancé**:
- Séparer modèles lecture/écriture
- Event Sourcing pour audit complet
- Projections read-optimisées

**Multi-tenancy**:
- Tenant context via Shared kernel
- Isolation données par Organization

---

**Auteur**: Claude Code
**Date**: Décembre 2025
**Objectif**: Démonstration architecture DDD + patterns pour entretien Preventeo

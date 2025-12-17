# Entretien Technique - Questions/Réponses Architecture

## Vue d'ensemble

Document pédagogique consolidant justifications choix architecturaux RiskTracker.
**Contexte** : Préparation entretien technique Preventeo - Patterns DDD/CQRS/Event-Driven

---

## 1. DDD & Architecture en Couches

### Question
> Approche DDD avec séparation Domain/Application/Infrastructure pour app cette taille : pas sur-architecturé ?

### Réponse

**Justification DDD adapté ici** :

#### Complexité métier réelle
- Workflow risques : transitions états validées (draft → open → assessed → mitigated → closed)
- Calculs scores : stratégies multiples interchangeables
- Permissions granulaires : RBAC par rôle
- Historisation/audit : traçabilité obligatoire conformité
- **Logique métier ne doit pas fuir dans contrôleurs/infrastructure**

#### Évolutivité prévisible
- Multi-tenancy organisations isolées
- Workflows configurables dynamiquement
- Nouvelles stratégies calcul
- Intégrations externes (Slack, webhooks)
- → Structure doit supporter évolution sans refonte

#### Testabilité critique
- Domain isolé = tests unitaires purs (sans DB/framework)
- Logique métier testable indépendamment
- Important pour conformité réglementaire

**Oui sur-architecturé si** :
- Simple CRUD sans logique métier
- Pas évolution prévue
- Équipe junior inexpérimentée

**Non adapté parce que** :
- Projet démo compétences architecture
- Logique métier justifie isolation (State, Strategy patterns)
- Base saine évolutions futures
- Coût initial compensé maintenabilité long terme

**Compromis acceptable** :
- Feature-based structure limite overhead (pas full tactical DDD partout)
- ValueObjects seulement où pertinent (Status, Score, Severity)
- Pas Event Sourcing/CQRS complet (overkill)

**Verdict** : Architecture appropriée pour app métier évolutive avec workflows complexes.

---

## 2. CQRS & Command/Query Handlers

### Question
> Commands/Handlers vs Services Symfony classiques : pourquoi séparer intentions écriture ?

### Réponse

**Ce que ça apporte** :

#### 1. Intention explicite
```php
// Command : intention claire traçable
new CreateRiskCommand($title, $description, $severity, $probability);

// vs Service classique : méthode générique
$riskService->create($data); // Fait quoi exactement ?
```

#### 2. Validation au bon endroit
- **Command** = DTO validé (contraintes métier)
- **Handler** = orchestration logique
- **Domain** = règles métier pures
- → Séparation responsabilités claire

#### 3. Testabilité améliorée
```php
// Test handler isolément
$handler = new CreateRiskHandler($repo, $calculator);
$risk = $handler->handle($command);

// vs Service avec dépendances mixées difficile isoler
```

#### 4. Évolutivité patterns
- Facile ajouter middleware (validation, logging, permissions)
- Prêt pour bus messages (Symfony Messenger)
- Base Event Sourcing si besoin futur

**Service Symfony classique problèmes** :
```php
class RiskService {
    public function create(array $data): Risk { ... }
    public function update(Risk $risk, array $data): void { ... }
}
```

- Méthodes deviennent fourre-tout (50+ lignes)
- Validation dispersée (controller + service)
- Difficile tracer intentions métier
- Couplage fort framework
- Tests complexes (mocker toutes deps)

**Avantage RiskTracker** :
- `ChangeRiskStatusCommand` : workflow critique validations transitions
- `RecalculateScoreCommand` : logique isolée stratégies
- `AssignRiskToSiteCommand` : règles affectation complexes

→ Chaque commande encapsule 1 règle métier testable unitairement

**Coût** : Plus fichiers (Command + Handler vs 1 Service)
**Gain** : Clarté intentions, testabilité, maintenabilité

**Verdict** : CRUD simple = overkill. Workflows métier règles complexes (gestion risques conformité) = justifié.

Pattern retrouvé plateformes SaaS métier (Preventeo) où traçabilité actions critiques essentielle.

---

## 3. Value Objects vs Types Primitifs

### Question
> RiskScore/RiskStatus comme VOs au lieu de int/string : bénéfices concrets vs contraintes Doctrine ?

### Réponse

**Problème Primitive Obsession** :
```php
// Approche primitive (anti-pattern)
class Risk {
    private int $score;        // Quelle plage ? Négatif valide ?
    private string $status;    // "open" ou "Open" ou "OPEN" ?
}

// Code métier pollué validations répétées
if ($risk->getScore() < 0 || $risk->getScore() > 100) {
    throw new \InvalidArgumentException();
}
```

### Bénéfices concrets Value Objects

#### 1. Validation centralisée
```php
// RiskScore.php
private function __construct(private int $value) {
    if ($value < 0 || $value > 100) {
        throw new InvalidRiskScoreException();
    }
}

// Impossible créer score invalide
$score = RiskScore::fromInt(150); // ❌ Exception
$score = RiskScore::fromInt(75);  // ✅ Garanti valide
```

#### 2. Logique métier encapsulée
```php
class RiskScore {
    public function isCritical(): bool {
        return $this->value >= 80;
    }

    public function isAcceptable(): bool {
        return $this->value < 20;
    }
}

// vs primitive : logique dispersée répétée 15 fois codebase
if ($risk->getScore() >= 80) { ... }
```

#### 3. Type safety strict
```php
function escalateRisk(RiskScore $score): void {
    // Impossible passer int par erreur
    // Impossible passer score invalide
}

// vs primitive
function escalateRisk(int $score): void {
    // Peut recevoir n'importe quel int (42, -999, ...)
}
```

#### 4. Immutabilité garantie
```php
class RiskScore {
    private function __construct(private readonly int $value) {}

    public function increase(int $points): self {
        return new self($this->value + $points); // Nouveau VO
    }
}

// Impossible modifier accidentellement
$score->value = 999; // ❌ Erreur compilation
```

#### 5. Expressivité code
```php
// Clair auto-documenté
$risk = new Risk(
    title: 'Incendie',
    severity: Severity::HIGH,
    probability: Probability::MEDIUM,
    status: RiskStatus::DRAFT
);

// vs obscur
$risk = new Risk('Incendie', 3, 2, 'draft'); // 3 et 2 = quoi ?
```

### Contraintes Doctrine

**Configuration nécessaire** :
```php
// Embeddable
#[ORM\Embeddable]
class RiskScore {
    #[ORM\Column(type: 'integer')]
    private int $value;
}

// Dans Risk entity
#[ORM\Embedded]
private RiskScore $score;
```

**Problèmes** :
- Config XML/YAML/annotations
- Queries DQL verbeux : `WHERE r.score.value > 50`

**Alternative Custom Types** :
```php
// App\Doctrine\Type\RiskScoreType
class RiskScoreType extends Type {
    public function convertToPHPValue($value, AbstractPlatform $platform) {
        return $value !== null ? RiskScore::fromInt($value) : null;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform) {
        return $value?->getValue();
    }
}
```

**Avantages** :
- Transparent DB (colonne `int` simple)
- Queries DQL normaux : `WHERE r.score > 50`
- Auto-conversion PHP ↔ DB

**Inconvénient** : Config `doctrine.yaml`

### Quand VOs overkill ?

**Éviter si** :
- Données purement techniques (IDs auto-générés)
- Pas règles validation
- Jamais logique métier associée
- Simple label affichage

**Exemple** : `createdAt` en `DateTime` natif PHP suffit

### ROI mesuré RiskTracker

**Bugs prévenus** : Impossible statut invalide DB
**Tests simplifiés** : VO testés une fois, réutilisés partout
**Refactoring sûr** : Changer logique score → 1 seul fichier
**Documentation vivante** : `Severity::HIGH` auto-expliqué

**Coût** : +2-3 fichiers par VO (~30 lignes)
**Gain** : Robustesse + maintenabilité

**Verdict** : Overhead compensé dès logique métier > CRUD trivial. App conformité réglementaire = non-négociable.

---

## 4. API Platform & Processors Personnalisés

### Question
> Pourquoi RiskProcessor custom au lieu mécanisme persistence standard API Platform ?

### Réponse

**Conflit fondamental** :

### API Platform par défaut
```php
#[ApiResource]
class Risk {
    // API Platform génère :
    // POST /risks → Doctrine persist() direct
    // PUT /risks/1 → Doctrine flush() direct
}
```

**Problème** : Bypass complet logique métier !

### Sans Processor custom
```
HTTP POST /risks
    ↓
API Platform Deserializer (DTO → Entity)
    ↓
Doctrine persist() DIRECT      ← ❌ Aucune validation métier
    ↓
Response 201
```

**Conséquences désastreuses** :
- Calcul score jamais exécuté
- Workflow statut pas validé
- Events jamais dispatchés
- Règles métier ignorées
- Tests domaine inutiles (contournés prod)

### Exemple problématique
```php
// Sans processor : requête POST directe
POST /risks
{
    "status": "closed"  // ❌ Passer direct "closed" sans workflow !
}

// API Platform persiste tel quel → DB inconsistante
```

### Solution : Processor personnalisé

```php
// RiskProcessor.php
public function process($data, Operation $operation, array $context = []) {
    return match($operation->getMethod()) {
        'POST' => $this->commandBus->dispatch(
            new CreateRiskCommand(
                $data->title,
                $data->severity,
                $data->probability
            )
        ),
        'PATCH' => $this->commandBus->dispatch(
            new UpdateRiskCommand($data)
        ),
    };
}
```

### Flux corrigé
```
HTTP POST /risks
    ↓
API Platform Deserializer (validation DTO)
    ↓
RiskProcessor::process()
    ↓
CreateRiskCommand dispatch
    ↓
CreateRiskHandler                  ← ✅ Logique métier centralisée
  - Validation règles
  - Calcul score automatique
  - Status initial forcé "draft"
  - Events dispatched
    ↓
Repository persist()
    ↓
Response 201
```

### Bénéfices concrets

#### 1. Logique métier garantie
```php
// CreateRiskHandler.php
public function __invoke(CreateRiskCommand $command): Risk {
    // ✅ Impossible créer Risk sans passer ici
    $score = $this->scoreCalculator->calculate(
        $command->severity,
        $command->probability
    );

    $risk = Risk::create(
        title: $command->title,
        status: RiskStatus::DRAFT  // ← Forcé, non négociable
    );

    $this->eventDispatcher->dispatch(new RiskCreated($risk));

    return $this->repository->save($risk);
}
```

#### 2. Point entrée unique
```
Console Command ──┐
API REST      ────┼──→ CreateRiskCommand ──→ Handler ──→ Domain
Import CSV    ────┘
Webhook       ────┘
```

Logique métier exécutée **quelle que soit** origine.

#### 3. Testabilité préservée
```php
// Test unitaire handler (pas besoin HTTP)
$handler = new CreateRiskHandler($repo, $calculator, $dispatcher);
$risk = $handler(new CreateRiskCommand(...));

$this->assertEquals(RiskStatus::DRAFT, $risk->getStatus());
```

#### 4. Traçabilité actions
```php
// Middleware possible sur bus
class AuditMiddleware {
    public function handle(Command $command, callable $next) {
        $this->logger->info('Command executed', [
            'command' => $command::class,
            'user' => $this->security->getUser()
        ]);
        return $next($command);
    }
}
```

### Alternative refusée : Event Subscribers

```php
// Approche tentante mais fragile
#[AsEntityListener]
class RiskEntityListener {
    public function prePersist(Risk $risk): void {
        // ❌ Logique métier dans lifecycle Doctrine
        $score = $this->calculateScore($risk);
        $risk->setScore($score);
    }
}
```

**Pourquoi refusé** :
- Couplage fort Doctrine
- Difficile tester (besoin EntityManager)
- Ordre execution listeners incertain
- Logique métier "cachée" infrastructure
- Impossible utiliser sans Doctrine (tests purs)

### Quand c'est overkill ?

**Processor custom inutile si** :
- Simple CRUD sans règles métier
- Aucun calcul/transformation
- Pas events dispatcher
- App purement technique (logs, metrics)

**Exemple** : Table `config_settings` (clé/valeur) → API Platform direct suffit

### Réponse finale

**Pourquoi court-circuiter API Platform ?**

Parce que **persistence ≠ logique métier**.

**API Platform excellent pour** :
- Sérialisation/Désérialisation
- Validation DTO (contraintes Symfony)
- Pagination/Filtres
- Documentation OpenAPI

**Pas pour** :
- Calculs métier
- Workflows
- Événements
- Règles business complexes

**Processor custom** = pont propre entre API REST (technique) et Domain (métier).

**Coût** : +50 lignes config
**Gain** : Architecture testable, maintenable, évolutive

App conformité réglementaire (Preventeo) où **traçabilité** et **garanties métier** critiques → non-négociable.

---

## 5. Événements & Asynchronisme

### Question
> Comment évoluer RiskStatusChangedSubscriber pour ajouter notifications email managers ? Risques approche synchrone vs asynchrone ?

### Réponse

### Évolution : Ajout notifications

**Approche recommandée : Subscriber additionnel**

```php
// Situation actuelle
class RiskStatusChangedSubscriber {
    public function onRiskStatusChanged(RiskStatusChanged $event): void {
        // Historisation seulement
        $this->historyRepository->save(
            new RiskHistory($event->getRisk())
        );
    }
}

// Nouveau subscriber
class RiskEscalationNotifier {
    public function onRiskStatusChanged(RiskStatusChanged $event): void {
        $risk = $event->getRisk();

        if (!$risk->getScore()->isCritical()) {
            return;
        }

        $managers = $this->userRepository->findByRole(Role::RISK_MANAGER);

        foreach ($managers as $manager) {
            $this->mailer->send(
                to: $manager->getEmail(),
                subject: "Risque critique : {$risk->getTitle()}"
            );
        }
    }
}
```

**Avantages** :
- Séparation responsabilités (1 subscriber = 1 concern)
- Facile activer/désactiver (config services)
- Testable unitairement
- Ordre execution configurable (priority)

**❌ Éviter mélanger dans subscriber existant** :
- Violation Single Responsibility
- Impossible désactiver emails sans toucher historisation
- Tests couplés

### Risques approche synchrone

**Problème concret** :
```php
// Controller
public function changeStatus(Risk $risk): Response {
    $this->commandBus->dispatch(
        new ChangeRiskStatusCommand($risk->getId(), RiskStatus::ASSESSED)
    );

    // ⏱️ Bloqué jusqu'à :
    // 1. Persist DB (50ms)
    // 2. Historisation (20ms)
    // 3. Email envoi (2000ms)  ← ❌ Timeout SMTP
    // 4. Slack webhook (500ms)
    // = 2570ms réponse HTTP

    return new JsonResponse(['status' => 'ok']); // Après 2.5s !
}
```

**Impact utilisateur** :
```
User clique "Valider" → ⏳ 3s → Timeout 504
```

**Cas échec critiques** :

#### 1. Service externe down
```php
public function onRiskStatusChanged(RiskStatusChanged $event): void {
    $this->saveHistory($event);     // ✅ Réussi

    $this->mailer->send(...);       // ❌ SMTP timeout 30s
                                     // → User attend 30s
                                     // → 503 Service Unavailable

    $this->slackClient->post(...);  // Jamais exécuté (exception)
}
```

#### 2. Effet domino
```
10 users changent statut simultanément
  → 10 emails envoyés synchrone
  → File SMTP saturée
  → Timeout tous requests
  → App down
```

#### 3. Transaction DB bloquée
```php
$this->em->beginTransaction();
$risk->changeStatus(RiskStatus::CLOSED);
$this->em->flush();

$this->eventDispatcher->dispatch(new RiskStatusChanged($risk));
// ⚠️ Si email 5s, transaction DB ouverte 5s
// → Locks tables, deadlocks possibles

$this->em->commit();
```

### Quand passer asynchrone ?

**Critères déclencheurs** :

| Critère | Synchrone OK | Asynchrone obligatoire |
|---------|--------------|------------------------|
| **Latence action** | < 100ms | > 500ms |
| **Dépendance externe** | Aucune | API/SMTP/Webhook |
| **Volume événements** | < 10/min | > 100/min |
| **Criticité delivery** | Échec acceptable | Retry obligatoire |
| **Cohérence requise** | Immédiate | Éventuelle OK |

**RiskTracker décision** :

**Garder synchrone** :
- ✅ **Historisation** : rapide (insert DB), critique (cohérence immédiate)
- ✅ **Validation permissions** : rapide, bloquant
- ✅ **Calcul score** : rapide (< 10ms), déterministe

**Passer asynchrone** :
- 🔄 **Email managers** : lent (SMTP), non-critique (retry OK)
- 🔄 **Slack webhooks** : réseau externe, peut échouer
- 🔄 **Export PDF rapports** : CPU-intensive, non-bloquant
- 🔄 **Sync CRM externe** : API tierce, retry nécessaire

### Implémentation Symfony Messenger

**Configuration** :
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async_priority_high:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: high_priority

        routing:
            App\Risk\Domain\Event\RiskStatusChanged: async_priority_high
```

**Subscriber devient Message Handler** :
```php
// Avant : Subscriber synchrone
class RiskEscalationNotifier implements EventSubscriberInterface {
    public function onRiskStatusChanged(RiskStatusChanged $event): void {
        // ❌ Bloque request HTTP
        $this->mailer->send(...);
    }
}

// Après : Message Handler asynchrone
#[AsMessageHandler]
class SendRiskEscalationEmailHandler {
    public function __invoke(RiskStatusChanged $message): void {
        // ✅ Exécuté par worker séparé
        $risk = $this->riskRepository->find($message->riskId);

        $managers = $this->userRepository->findByRole(Role::RISK_MANAGER);

        foreach ($managers as $manager) {
            $this->mailer->send(...);
        }
    }
}
```

**Dispatcher adapté** :
```php
// ChangeRiskStatusHandler.php
public function __invoke(ChangeRiskStatusCommand $command): void {
    $risk = $this->repository->find($command->riskId);
    $risk->changeStatus($command->newStatus);
    $this->repository->save($risk);

    // Dispatch asynchrone
    $this->messageBus->dispatch(
        new RiskStatusChanged($risk->getId(), ...)
    );

    // ✅ Response HTTP immédiate (sans attendre email)
}
```

**Worker démon** :
```bash
# Consomme messages background
php bin/console messenger:consume async_priority_high -vv

# Supervision systemd/supervisord
[program:messenger-worker]
command=php /var/www/bin/console messenger:consume async_priority_high
numprocs=4
autostart=true
autorestart=true
```

### Gestion erreurs asynchrone

**Retry automatique** :
```yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            async:
                retry_strategy:
                    max_retries: 3
                    delay: 1000        # 1s
                    multiplier: 2      # Backoff exponentiel
                    max_delay: 10000   # 10s max
```

**Dead Letter Queue** :
```php
#[AsMessageHandler]
class SendEmailHandler {
    public function __invoke(RiskStatusChanged $message): void {
        try {
            $this->mailer->send(...);
        } catch (TransportException $e) {
            // Retry automatique 3x
            throw $e;
        } catch (\Exception $e) {
            // Erreur fatale → failed queue
            $this->logger->critical('Email failed', [
                'risk_id' => $message->riskId
            ]);

            throw new UnrecoverableMessageHandlingException();
        }
    }
}

// Monitor failed messages
php bin/console messenger:failed:show
php bin/console messenger:failed:retry
```

### Architecture hybride recommandée

```php
// Event principal (synchrone critique)
class RiskStatusChangedSubscriber {
    public function onRiskStatusChanged(RiskStatusChanged $event): void {
        // ✅ Synchrone : historisation (rapide, critique)
        $this->historyRepository->save(
            new RiskHistory($event->getRisk())
        );

        // 🔄 Dispatch async : notifications (lent, non-critique)
        $this->messageBus->dispatch(
            new SendRiskEscalationNotification($event->getRisk()->getId())
        );
    }
}
```

### Seuils décision concrets

**Rester synchrone** :
- App < 100 users
- < 50 risques changent statut/jour
- Pas intégrations externes
- Budget limité (pas infra workers)

**Passer asynchrone** :
- App > 500 users
- \> 500 événements/jour
- Intégrations SMTP/Slack/CRM
- SLA réponse HTTP < 200ms
- Besoin retry automatique

**RiskTracker actuel** : Synchrone OK (démo)
**Production Preventeo** : Asynchrone obligatoire (volume + intégrations)

**Verdict** : Synchrone suffisant démo, asynchrone nécessaire production dès intégrations externes ou volume significatif.

---

## Patterns Implémentés - Synthèse

### Patterns Structurels

| Pattern | Localisation | Justification |
|---------|--------------|---------------|
| **Repository** | `Risk/Domain/Repository/` | Abstraction accès données, testabilité |
| **Service Layer** | `Risk/Application/Command/` | Orchestration logique métier |
| **DTO/Transformer** | `Risk/Infrastructure/ApiPlatform/` | Validation/transformation API |

### Patterns Comportementaux

| Pattern | Localisation | Justification |
|---------|--------------|---------------|
| **State** | `Risk/Domain/ValueObject/RiskStatus.php` | Workflow statuts (draft/open/assessed/closed) |
| **Strategy** | `Risk/Domain/Service/ScoreCalculatorInterface.php` | Calculs scores variables contexte |
| **Observer** | `Risk/Domain/Event/` + Subscribers | Notifications événements (changement statut) |
| **Chain of Responsibility** | Middleware Command Bus | Validation permissions chaîne |

### Patterns Créationnels

| Pattern | Localisation | Justification |
|---------|--------------|---------------|
| **Factory** | `Risk/Domain/Entity/Risk::create()` | Création entités cohérentes |
| **Builder** | (À venir) Rapports complexes | Construction rapports étape par étape |

### Patterns Architecturaux

| Pattern | Niveau | Justification |
|---------|--------|---------------|
| **CQRS** | Application | Séparation Commands/Queries |
| **Event-Driven** | Domain | Découplage via événements |
| **Layered Architecture** | Global | Domain/Application/Infrastructure |
| **Feature-Based** | Structure | Cohésion forte intra-feature |

---

## Principes SOLID Appliqués

### Single Responsibility
- 1 Handler = 1 Command = 1 Use Case
- 1 Subscriber = 1 Concern événement
- ValueObjects = logique métier isolée

### Open/Closed
- ScoreCalculatorInterface : nouvelles stratégies sans modifier existant
- Event Subscribers : nouveaux handlers sans toucher dispatcher

### Liskov Substitution
- Toutes implémentations `RiskRepositoryInterface` interchangeables
- Stratégies calcul polymorphes

### Interface Segregation
- `RiskRepositoryInterface` : méthodes spécifiques (pas god interface)
- Interfaces fines par use case

### Dependency Inversion
- Domain dépend abstractions (`RepositoryInterface`)
- Infrastructure implémente interfaces Domain
- Inversion contrôle via DI Symfony

---

## Anti-Patterns Évités

| Anti-Pattern | Solution RiskTracker |
|--------------|----------------------|
| **Primitive Obsession** | ValueObjects (RiskScore, RiskStatus) |
| **Anemic Domain Model** | Logique métier dans entités (`Risk::changeStatus()`) |
| **God Object** | Feature-based structure, handlers spécialisés |
| **Big Ball of Mud** | Layers DDD claires |
| **Magic Numbers** | Constantes ValueObjects (`Severity::HIGH`) |
| **Shotgun Surgery** | Logique centralisée (1 fichier par règle) |

---

## Points Clés Entretien

### Défense choix techniques

**API Platform** :
- Productivité (doc auto OpenAPI)
- Flexibilité (Processors customs)
- Écosystème mature

**Strategy Pattern** :
- Flexibilité calculs métier évolutifs
- Testabilité isolée stratégies
- Configuration runtime

**State Pattern** :
- Maintenance workflow claire
- Validations transitions explicites
- Évolution règles business simple

**Vue 3 Composition** :
- Réutilisabilité logique (composables)
- TypeScript robustesse
- Performance (Vite)

### Questions probables

**"Pourquoi State Pattern workflow ?"**
→ Transitions validées, maintenance claire, évolution simple

**"Comment gérer évolution calculs scores ?"**
→ Strategy Pattern : nouvelles stratégies sans casser existant

**"Scalabilité multi-tenants ?"**
→ Isolation tenant ID, RLS Postgres, Doctrine filters

**"Stratégie tests workflow complexe ?"**
→ Pyramide : Unit (logic) → Integration (DB) → Functional (API)

**"Performance API filtres complexes ?"**
→ Index DB, pagination, cache HTTP, CQRS read models

---

## Métriques Qualité

```bash
# Coverage
vendor/bin/phpunit --coverage-text
# Target: > 80% Domain/Application

# Static analysis
vendor/bin/phpstan analyse --level 9
# Target: 0 erreurs

# Code style
vendor/bin/phpcs
# Target: PSR-12 strict

# Behat scenarios
vendor/bin/behat
# Target: Tous scénarios métier critiques couverts
```

---

## Évolutions Futures

### Court terme (post-entretien)
- Multi-tenancy (organisations isolées)
- Workflow configurable dynamiquement
- Analytics avancées (graphes)

### Moyen terme
- ML prédiction risques
- Intégration Slack/Teams webhooks
- Mobile app (Vue + Capacitor)

### Long terme
- Event Sourcing audit complet
- API webhooks externes
- SSO (OAuth2/SAML)

---

## Ressources

- **DDD** : Eric Evans - "Domain-Driven Design"
- **Patterns** : [Refactoring Guru](https://refactoring.guru/design-patterns)
- **CQRS** : Martin Fowler - [CQRS Pattern](https://martinfowler.com/bliki/CQRS.html)
- **Symfony** : [Best Practices](https://symfony.com/doc/current/best_practices.html)
- **Testing** : Kent Beck - "Test-Driven Development"

---

**Dernière mise à jour** : 16 décembre 2025
**Objectif** : Préparation entretien technique Preventeo

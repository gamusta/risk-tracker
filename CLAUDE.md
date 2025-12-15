# RiskTracker - Projet Fil Rouge

## Contexte Métier
Plateforme SaaS de gestion des risques et incidents pour entreprises multi-sites. Permet d'identifier, évaluer, suivre et clôturer les risques selon workflows normalisés. Similaire à solutions conformité réglementaire type Preventeo.

## Finalité
Application complète démontrant maîtrise patterns architecture, API REST moderne, workflows métier et front réactif. Sert de support révision technique et démo compétences pour entretien Preventeo.

---

## Features Core

### Gestion Risques
- CRUD risques (titre, description, type, gravité, probabilité)
- Workflow statuts: `draft → open → assessed → mitigated → closed`
- Calcul score risque automatique (stratégies multiples)
- Catégorisation hiérarchique (Sécurité/Environnement/Social/Cyber)
- Affectation sites et responsables

### Gestion Sites
- CRUD sites (nom, adresse, typologie)
- Liaison N risques par site

### Utilisateurs & Droits
- Rôles: Admin, Risk Manager, Viewer
- Auth JWT
- Permissions granulaires actions

### Tracking & Audit
- Historique modifications risques
- Timeline événements
- Notifications changements statuts

### Exports & Rapports
- PDF synthèse risques par site
- Excel export données filtrées
- Dashboard statistiques

---

## Stack Technique

### Backend
- PHP 8.3
- Symfony 6.4/7.x
- API Platform 3.x
- Doctrine ORM
- MariaDB/MySQL
- PHPUnit + Behat

### Frontend
- Vue.js 3 (Composition API)
- TypeScript
- Pinia (state management)
- Vite
- Tailwind CSS
- Axios

### DevOps
- Docker + Docker Compose
- Makefile commandes
- Git/GitFlow

---

## Architecture Globale

```
risk-tracker/
├── backend/                   # API Symfony
│   ├── config/
│   ├── src/
│   │   ├── Entity/           # Risk, Site, User, Category, RiskHistory
│   │   ├── Repository/
│   │   ├── Service/          # RiskService, ScoreCalculator, WorkflowManager
│   │   ├── State/            # RiskStateMachine, States (Draft, Open, Assessed...)
│   │   ├── Strategy/         # ScoreStrategy (Simple, Matrix, Advanced)
│   │   ├── Factory/          # ReportFactory, NotificationFactory
│   │   ├── EventListener/    # RiskStatusChangedListener
│   │   ├── DTO/              # RiskDTO, SiteDTO
│   │   └── Controller/       # API REST endpoints
│   ├── tests/
│   ├── docker/
│   └── composer.json
│
├── frontend/                  # App Vue.js
│   ├── src/
│   │   ├── components/       # RiskList, RiskForm, RiskCard, SiteSelector
│   │   ├── composables/      # useRisks, useAuth, useWorkflow
│   │   ├── stores/           # riskStore, authStore, siteStore (Pinia)
│   │   ├── views/            # DashboardView, RisksView, SitesView
│   │   ├── services/         # api.service.ts
│   │   └── router/
│   ├── package.json
│   └── vite.config.ts
│
├── docker-compose.yml
├── Makefile
└── README.md
```

---

## Patterns à Implémenter

### Structurels
- **Repository Pattern** → Accès données isolé
- **Service Layer** → Logique métier centralisée
- **DTO/Transformer** → Validation et transformation API

### Comportementaux
- **State Pattern** → Workflow statuts risques (draft/open/assessed/closed)
- **Strategy Pattern** → Calcul scores variables selon contexte
- **Observer Pattern** → Notifications événements (changement statut)
- **Chain of Responsibility** → Validation permissions en chaîne

### Créationnels
- **Factory** → Création rapports/notifications
- **Builder** → Construction rapports complexes étape par étape

### Frontend
- **Composables Pattern** → Logique réutilisable Vue 3
- **Store Pattern** → État global Pinia
- **Component Composition** → Composants modulaires

---

## Roadmap Progressive

### Étape 1 - Base CRUD (45min)
**Objectif**: Structure projet + CRUD basique
- Entités: Risk, Site, User
- API REST: POST/GET/PUT/DELETE risks
- Front Vue: Liste + Form création
- **Patterns**: Repository, Service Layer

### Étape 2 - Workflow Statuts (45min)
**Objectif**: Gestion états et transitions
- Risk: draft → open → assessed → closed
- Validation transitions autorisées
- Historisation changements
- **Pattern**: State Pattern ⭐

### Étape 3 - Calcul Scores Risque (45min)
**Objectif**: Évaluation dynamique
- Stratégies multiples: Simple (G×P), Matrice, Pondéré
- Changement stratégie à la volée
- Recalcul automatique
- **Pattern**: Strategy Pattern ⭐

### Étape 4 - Catégorisation (30min)
**Objectif**: Hiérarchie catégories
- Arbre: Sécurité > Incendie > Électrique
- Navigation catégories
- **Pattern**: Composite

### Étape 5 - Notifications (45min)
**Objectif**: Événements asynchrones
- Events Symfony changement statut
- Email/Slack notifications
- Log timeline
- **Pattern**: Observer/EventDispatcher ⭐

### Étape 6 - Export Rapports (45min)
**Objectif**: Génération documents
- PDF/Excel formats multiples
- Templates variables
- **Pattern**: Builder + Factory

### Étape 7 - Historique/Audit (30min)
**Objectif**: Traçabilité complète
- Versionning modifications
- Restoration état antérieur
- **Pattern**: Memento

### Étape 8 - Vue.js Avancé (1h)
**Objectif**: Front moderne et réactif
- Composables réutilisables
- Pinia stores optimisés
- Formulaires dynamiques conditionnels

---

## Contraintes Qualité

### Code
- PSR-12 (PHP)
- Strict types PHP/TypeScript
- SOLID principles
- Clean Code

### Tests
- PHPUnit coverage >70%
- Behat scenarios métier
- Tests unitaires patterns critiques

### Documentation
- OpenAPI auto-générée (API Platform)
- PHPDoc complet
- README étapes installation
- Commentaires patterns utilisés

### Versioning
- Commits conventionnels (feat/fix/refactor)
- GitFlow (main/develop/feature/*)
- Tags versions

---

## Workflow Développement

1. **Backend feature complète**
    - Entité + Repository
    - Service + Pattern
    - API endpoint
    - Tests unitaires
    - Tests fonctionnels

2. **Frontend consommation**
    - Service API
    - Store Pinia
    - Composants Vue
    - Validation UX

3. **Refactoring patterns**
    - Extraction logique commune
    - Application SOLID
    - Documentation patterns

4. **Validation**
    - Tests E2E
    - Review code
    - Performance

---

## Setup Initial

### Backend
```bash
composer create-project symfony/skeleton:"6.4.*" backend
cd backend
composer require api-platform orm doctrine/annotations validator security jwt
composer require --dev phpunit symfony/test-pack behat phpstan
```

### Frontend
```bash
npm create vite@latest frontend -- --template vue-ts
cd frontend
npm install pinia vue-router axios
npm install -D tailwindcss @tailwindcss/forms
```

### Docker
```yaml
# docker-compose.yml
version: '3.8'
services:
  php:
    build: ./backend/docker/php
    volumes:
      - ./backend:/var/www
  
  nginx:
    image: nginx:alpine
    ports:
      - "8000:80"
    volumes:
      - ./backend:/var/www
      - ./backend/docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
  
  db:
    image: mariadb:10.11
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: risktracker
    ports:
      - "3306:3306"
  
  frontend:
    build: ./frontend
    ports:
      - "5173:5173"
    volumes:
      - ./frontend:/app
```

---

## Commandes Utiles (Makefile)

```makefile
.PHONY: install start stop test

install:
	docker-compose build
	docker-compose run php composer install
	docker-compose run frontend npm install

start:
	docker-compose up -d

stop:
	docker-compose down

test:
	docker-compose exec php bin/phpunit
	docker-compose exec php vendor/bin/behat

db-reset:
	docker-compose exec php bin/console doctrine:database:drop --force --if-exists
	docker-compose exec php bin/console doctrine:database:create
	docker-compose exec php bin/console doctrine:migrations:migrate -n
	docker-compose exec php bin/console doctrine:fixtures:load -n
```

---

## Notes pour Entretien Preventeo

### Points à mettre en avant
- Architecture découplée API/Front
- Patterns adaptés métier conformité
- Workflows métier maîtrisés
- Code testable et maintenable
- Expérience SaaS similaire (Kleap, RestoPro)

### Questions probables discussion
- "Pourquoi State Pattern pour workflow?"
- "Comment gérer évolution calculs scores?"
- "Scalabilité multi-tenants?"
- "Stratégie tests workflow complexe?"
- "Performance API avec filtres complexes?"

### Défense choix techniques
- API Platform: productivité + doc auto
- Strategy: flexibilité calculs métier
- State: maintenance workflow claire
- Vue 3 Composition: réutilisabilité logique
- TypeScript: robustesse front

---

## Évolutions Futures (Post-Entretien)

- Multi-tenancy (organisations isolées)
- Workflow configurable dynamiquement
- ML prédiction risques
- Intégration Slack/Teams
- Mobile app (Vue + Capacitor)
- Analytics avancées (graphes)
- Import/Export masse (CSV/Excel)
- API webhooks
- SSO (OAuth2/SAML)

---

## Ressources

### Documentation
- [Symfony Docs](https://symfony.com/doc/current/index.html)
- [API Platform](https://api-platform.com/docs/)
- [Vue 3 Guide](https://vuejs.org/guide/introduction.html)
- [Pinia](https://pinia.vuejs.org/)

### Patterns
- [Refactoring Guru - Design Patterns](https://refactoring.guru/design-patterns)
- [SourceMaking Patterns](https://sourcemaking.com/design_patterns)

### Tests
- [PHPUnit](https://phpunit.de/)
- [Behat](https://docs.behat.org/)

---

**Dernière mise à jour**: Décembre 2025  
**Objectif**: Test technique Preventeo - 18 décembre 2025
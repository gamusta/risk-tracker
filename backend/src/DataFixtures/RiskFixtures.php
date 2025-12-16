<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Risk\Domain\Entity\Risk;
use App\Risk\Domain\ValueObject\Probability;
use App\Risk\Domain\ValueObject\RiskStatus;
use App\Risk\Domain\ValueObject\Severity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures: Données de test pour développement
 */
class RiskFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. Risque critique (5×5=25)
        $risk1 = Risk::create(
            title: 'Faille de sécurité critique - Injection SQL',
            type: 'security',
            severity: Severity::fromInt(5),
            probability: Probability::fromInt(5),
            description: 'Vulnérabilité critique permettant injection SQL dans formulaire login. Exposition données sensibles.'
        );
        $risk1->changeStatus(RiskStatus::OPEN);
        $risk1->assignToSite(1);
        $risk1->assignToUser(1);
        $manager->persist($risk1);

        // 2. Risque élevé (4×5=20)
        $risk2 = Risk::create(
            title: 'Incident incendie entrepôt stockage',
            type: 'environment',
            severity: Severity::fromInt(4),
            probability: Probability::fromInt(5),
            description: 'Risque incendie élevé dû stockage produits inflammables sans système extinction automatique.'
        );
        $risk2->changeStatus(RiskStatus::OPEN);
        $risk2->changeStatus(RiskStatus::ASSESSED);
        $risk2->assignToSite(2);
        $manager->persist($risk2);

        // 3. Risque moyen (3×3=9)
        $risk3 = Risk::create(
            title: 'Cyberattaque DDoS infrastructure',
            type: 'cyber',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(3),
            description: 'Risque attaque par déni de service sur serveurs principaux. Impact disponibilité services.'
        );
        $risk3->changeStatus(RiskStatus::OPEN);
        $risk3->changeStatus(RiskStatus::ASSESSED);
        $risk3->changeStatus(RiskStatus::MITIGATED);
        $manager->persist($risk3);

        // 4. Risque faible (2×2=4) - Clôturé
        $risk4 = Risk::create(
            title: 'Non-conformité affichage sécurité',
            type: 'social',
            severity: Severity::fromInt(2),
            probability: Probability::fromInt(2),
            description: 'Panneaux sécurité sortie secours non conformes normes. Impact mineur.'
        );
        $risk4->changeStatus(RiskStatus::OPEN);
        $risk4->close();
        $manager->persist($risk4);

        // 5. Risque draft (non encore ouvert)
        $risk5 = Risk::create(
            title: 'Évaluation risques psychosociaux',
            type: 'social',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(2),
            description: 'Évaluation préliminaire risques liés charge travail et stress équipes.'
        );
        $manager->persist($risk5);

        // 6. Risque environnemental élevé
        $risk6 = Risk::create(
            title: 'Pollution nappe phréatique stockage chimique',
            type: 'environment',
            severity: Severity::fromInt(5),
            probability: Probability::fromInt(3),
            description: 'Cuves stockage produits chimiques vieillissantes. Risque fuite contamination sols.'
        );
        $risk6->changeStatus(RiskStatus::OPEN);
        $risk6->changeStatus(RiskStatus::ASSESSED);
        $risk6->assignToSite(3);
        $manager->persist($risk6);

        // 7. Risque cyber moyen
        $risk7 = Risk::create(
            title: 'Ransomware par phishing collaborateurs',
            type: 'cyber',
            severity: Severity::fromInt(4),
            probability: Probability::fromInt(3),
            description: 'Risque infection ransomware via emails phishing ciblant collaborateurs.'
        );
        $risk7->changeStatus(RiskStatus::OPEN);
        $manager->persist($risk7);

        // 8. Risque sécurité faible - mitigé
        $risk8 = Risk::create(
            title: 'Accès non sécurisé local technique',
            type: 'security',
            severity: Severity::fromInt(2),
            probability: Probability::fromInt(3),
            description: 'Porte local technique sans badge contrôle accès.'
        );
        $risk8->changeStatus(RiskStatus::OPEN);
        $risk8->changeStatus(RiskStatus::ASSESSED);
        $risk8->changeStatus(RiskStatus::MITIGATED);
        $manager->persist($risk8);

        // 9. Risque critique cyber - assessed
        $risk9 = Risk::create(
            title: 'Exfiltration données par API non sécurisée',
            type: 'cyber',
            severity: Severity::fromInt(5),
            probability: Probability::fromInt(4),
            description: 'API REST sans authentification exposant données clients. Risque RGPD majeur.'
        );
        $risk9->changeStatus(RiskStatus::OPEN);
        $risk9->changeStatus(RiskStatus::ASSESSED);
        $risk9->assignToSite(1);
        $risk9->assignToUser(2);
        $manager->persist($risk9);

        // 10. Risque social moyen
        $risk10 = Risk::create(
            title: 'Accidents travail manutention manuelle',
            type: 'social',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(4),
            description: 'TMS liés manutention charges lourdes sans équipement adapté.'
        );
        $risk10->changeStatus(RiskStatus::OPEN);
        $risk10->assignToSite(2);
        $manager->persist($risk10);

        $manager->flush();
    }
}

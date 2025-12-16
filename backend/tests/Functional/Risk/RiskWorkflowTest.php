<?php

declare(strict_types=1);

namespace App\Tests\Functional\Risk;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test fonctionnel: Workflow complet Risk via API
 *
 * Teste le cycle de vie complet d'un risque:
 * - Création (draft)
 * - Transitions de statuts valides
 * - Rejet des transitions invalides
 * - Calcul automatique du score
 * - Réassessment (mise à jour severity/probability)
 */
final class RiskWorkflowTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function test_complete_workflow_from_creation_to_closure(): void
    {
        // 1. Créer un risque (état DRAFT)
        $this->client->request('POST', '/api/risks', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Critical Security Vulnerability',
            'description' => 'SQL injection in login form',
            'type' => 'security',
            'severity' => 5,
            'probability' => 4,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Critical Security Vulnerability', $response['title']);
        $this->assertEquals('draft', $response['status']);
        $this->assertEquals(20, $response['score']);
        $this->assertEquals('high', $response['scoreLevel']);  // 20 is high (16-24)

        $riskId = $response['id'];

        // 2. Transition DRAFT → OPEN
        $this->client->request('PATCH', "/api/risks/{$riskId}/status", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'status' => 'open',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('open', $response['status']);

        // 3. Transition OPEN → ASSESSED
        $this->client->request('PATCH', "/api/risks/{$riskId}/status", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'status' => 'assessed',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('assessed', $response['status']);

        // 4. Transition ASSESSED → MITIGATED
        $this->client->request('PATCH', "/api/risks/{$riskId}/status", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'status' => 'mitigated',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('mitigated', $response['status']);

        // 5. Transition MITIGATED → CLOSED
        $this->client->request('PATCH', "/api/risks/{$riskId}/status", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'status' => 'closed',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('closed', $response['status']);
    }

    public function test_rejects_invalid_status_transition(): void
    {
        // Créer un risque
        $this->client->request('POST', '/api/risks', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Test Risk',
            'type' => 'cyber',
            'severity' => 3,
            'probability' => 3,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $riskId = $response['id'];

        // Transition DRAFT → OPEN (valide)
        $this->client->request('PATCH', "/api/risks/{$riskId}/status", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'status' => 'open',
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Transition OPEN → MITIGATED (invalide, doit passer par ASSESSED)
        $this->client->request('PATCH', "/api/risks/{$riskId}/status", [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'status' => 'mitigated',
        ]));

        // Doit retourner 400 ou 422 (validation error)
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_score_recalculates_automatically_after_assessment(): void
    {
        // Créer un risque avec severity=2, probability=2 (score=4)
        $this->client->request('POST', '/api/risks', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Low Risk Initially',
            'type' => 'environment',
            'severity' => 2,
            'probability' => 2,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(4, $response['score']);
        $this->assertEquals('low', $response['scoreLevel']);

        $riskId = $response['id'];

        // Mettre à jour avec severity=5, probability=5 (score=25)
        $this->client->request('PUT', "/api/risks/{$riskId}", [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Now Critical Risk',
            'type' => 'environment',
            'severity' => 5,
            'probability' => 5,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(25, $response['score']);
        $this->assertEquals('critical', $response['scoreLevel']);
    }

    public function test_rejects_invalid_severity_value(): void
    {
        $this->client->request('POST', '/api/risks', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Invalid Risk',
            'type' => 'security',
            'severity' => 10,  // Invalide (max 5)
            'probability' => 3,
        ]));

        // Doit retourner 400 ou 422 (validation error)
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_rejects_title_too_short(): void
    {
        $this->client->request('POST', '/api/risks', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'AB',  // Trop court (min 3)
            'type' => 'security',
            'severity' => 3,
            'probability' => 3,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_rejects_invalid_risk_type(): void
    {
        $this->client->request('POST', '/api/risks', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Test Risk',
            'type' => 'invalid_type',  // Type invalide
            'severity' => 3,
            'probability' => 3,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_get_all_risks_returns_collection(): void
    {
        // Créer plusieurs risques
        $this->client->request('POST', '/api/risks', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Risk 1',
            'type' => 'security',
            'severity' => 3,
            'probability' => 3,
        ]));

        $this->client->request('POST', '/api/risks', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Risk 2',
            'type' => 'cyber',
            'severity' => 4,
            'probability' => 4,
        ]));

        // Récupérer la collection
        $this->client->request('GET', '/api/risks');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // API Platform 4 returns array directly, not hydra:member
        $this->assertIsArray($response);
        $this->assertGreaterThanOrEqual(2, count($response));
    }

    public function test_assigns_risk_to_site(): void
    {
        // Créer un risque
        $this->client->request('POST', '/api/risks', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Site Risk',
            'type' => 'security',
            'severity' => 3,
            'probability' => 3,
            'siteId' => 42,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(42, $response['siteId']);
    }

    public function test_assigns_risk_to_user(): void
    {
        // Créer un risque
        $this->client->request('POST', '/api/risks', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Assigned Risk',
            'type' => 'security',
            'severity' => 3,
            'probability' => 3,
            'assignedToId' => 99,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(99, $response['assignedToId']);
    }
}

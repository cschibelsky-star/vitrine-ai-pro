<?php

namespace Tests\Feature;

use Tests\TestCase;

class CentroIaBrokerTest extends TestCase
{
    private string $endpoint = '/api/internal/centro-ia/execute';

    public function test_broker_rejects_request_without_internal_token(): void
    {
        config(['centro_ia.internal_token' => 'test-secret']);

        $response = $this->postJson($this->endpoint, [
            'project_id' => 'cursos-ia-mvp',
            'capability' => 'course_generation',
            'input' => ['user' => 'Crie o curso com base nas fontes.'],
        ], [
            'X-Vitrine-Project' => 'cursos-ia-mvp',
        ]);

        $response->assertStatus(401)
            ->assertJson(['ok' => false, 'error' => 'unauthorized']);
    }

    public function test_broker_rejects_project_identity_mismatch(): void
    {
        config(['centro_ia.internal_token' => 'test-secret']);

        $response = $this->postJson($this->endpoint, [
            'project_id' => 'cursos-ia-mvp',
            'capability' => 'course_generation',
            'input' => ['user' => 'Crie o curso com base nas fontes.'],
        ], [
            'Authorization' => 'Bearer test-secret',
            'X-Vitrine-Project' => 'outro-projeto',
        ]);

        $response->assertStatus(422)
            ->assertJson(['ok' => false, 'error' => 'project_identity_mismatch']);
    }

    public function test_broker_rejects_unregistered_capability(): void
    {
        config([
            'centro_ia.internal_token' => 'test-secret',
            'centro_ia.capabilities' => [],
        ]);

        $response = $this->postJson($this->endpoint, [
            'project_id' => 'cursos-ia-mvp',
            'capability' => 'unknown_capability',
            'input' => ['user' => 'Solicitação de teste do broker.'],
        ], [
            'Authorization' => 'Bearer test-secret',
            'X-Vitrine-Project' => 'cursos-ia-mvp',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'error' => 'capability_not_supported',
                'capability' => 'unknown_capability',
            ]);
    }
}

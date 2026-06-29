<?php

namespace Tests\Feature;

use Tests\TestCase;

class AgendamentoApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        $response = $this->getJson('/api/agendamentos');

        $response->assertStatus(200);
    }
}

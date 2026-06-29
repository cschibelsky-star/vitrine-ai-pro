<?php

namespace Tests\Feature;

use Tests\TestCase;

class ClienteApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        $response = $this->getJson('/api/clientes');

        $response->assertStatus(200);
    }
}

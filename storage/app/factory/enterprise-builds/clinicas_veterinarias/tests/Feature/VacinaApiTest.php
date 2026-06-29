<?php

namespace Tests\Feature;

use Tests\TestCase;

class VacinaApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        $response = $this->getJson('/api/vacinas');

        $response->assertStatus(200);
    }
}

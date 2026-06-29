<?php

namespace Tests\Feature;

use Tests\TestCase;

class CategoriaApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        $response = $this->getJson('/api/categorias');

        $response->assertStatus(200);
    }
}

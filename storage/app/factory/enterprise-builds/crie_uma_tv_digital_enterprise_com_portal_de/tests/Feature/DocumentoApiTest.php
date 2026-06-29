<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocumentoApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        $response = $this->getJson('/api/documentos');

        $response->assertStatus(200);
    }
}

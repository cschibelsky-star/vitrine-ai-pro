<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProntuarioApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        $response = $this->getJson('/api/prontuarios');

        $response->assertStatus(200);
    }
}

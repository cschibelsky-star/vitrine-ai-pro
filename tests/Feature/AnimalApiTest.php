<?php

namespace Tests\Feature;

use Tests\TestCase;

class AnimalApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        $response = $this->getJson('/api/animais');

        $response->assertStatus(200);
    }
}

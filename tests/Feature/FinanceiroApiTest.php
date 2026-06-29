<?php

namespace Tests\Feature;

use Tests\TestCase;

class FinanceiroApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        $response = $this->getJson('/api/financeiro');

        $response->assertStatus(200);
    }
}

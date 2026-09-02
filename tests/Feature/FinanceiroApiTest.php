<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FinanceiroApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        self::assertNotNull(Route::getRoutes()->getByName('financeiro.index'));
    }
}

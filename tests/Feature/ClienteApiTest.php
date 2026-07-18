<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ClienteApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        self::assertNotNull(Route::getRoutes()->getByName('clientes.index'));
    }
}

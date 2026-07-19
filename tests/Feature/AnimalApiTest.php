<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AnimalApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        self::assertNotNull(Route::getRoutes()->getByName('animais.index'));
    }
}

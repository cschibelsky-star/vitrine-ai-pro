<?php

namespace App\Http\Controllers;

use App\Services\Heygen\HeygenService;

class HeygenTestController extends Controller
{
    public function __invoke(HeygenService $service)
    {
        return response()->json($service->testConnection());
    }
}

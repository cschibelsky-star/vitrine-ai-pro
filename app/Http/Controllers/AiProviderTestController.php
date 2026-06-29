<?php

namespace App\Http\Controllers;

use App\Models\AiProvider;
use App\Services\Ai\AiExecutionService;

class AiProviderTestController extends Controller
{
    public function __invoke(AiProvider $provider, AiExecutionService $service)
    {
        $result = $service->testProvider($provider);

        return response()->json([
            'provider' => [
                'id' => $provider->id,
                'name' => $provider->name,
                'slug' => $provider->slug,
                'status' => $provider->status,
                'has_api_key' => filled($provider->api_key),
            ],
            'test' => $result,
        ]);
    }
}

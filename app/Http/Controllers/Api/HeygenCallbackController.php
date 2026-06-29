<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Heygen\HeygenService;
use Illuminate\Http\Request;

class HeygenCallbackController extends Controller
{
    public function handle(Request $request, HeygenService $service)
    {
        $job = $service->handleCallback($request->all());

        return response()->json([
            'ok' => true,
            'job_found' => (bool) $job,
            'job_id' => $job?->id,
            'status' => $job?->status,
        ]);
    }
}

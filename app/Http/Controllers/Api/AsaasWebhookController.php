<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Asaas\AsaasLicenseService;
use Illuminate\Http\Request;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request, AsaasLicenseService $service)
    {
        return response()->json($service->processWebhook($request->all()));
    }
}

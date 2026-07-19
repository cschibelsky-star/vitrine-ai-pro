<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Asaas\AsaasLicenseService;
use App\Support\WebhookAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsaasWebhookController extends Controller
{
    public function handle(
        Request $request,
        AsaasLicenseService $service,
        WebhookAuthenticator $authenticator,
    ): JsonResponse {
        if (! $authenticator->authorized($request, 'asaas')) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        return response()->json($service->processWebhook($request->all()));
    }
}

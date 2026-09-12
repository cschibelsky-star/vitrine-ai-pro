<?php

namespace App\Http\Controllers\Api;

use App\CommercialFactory\Services\CommercialFactoryIntakeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiteFactoryIntakeRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class SiteFactoryIntakeController extends Controller
{
    public function __invoke(
        SiteFactoryIntakeRequest $request,
        CommercialFactoryIntakeService $service
    ): JsonResponse {
        try {
            $report = $service->intake([
                'product' => $request->string('product')->toString(),
                'client' => $request->string('client')->toString(),
                'plan' => $request->string('plan')->toString(),
                'email' => $request->input('email'),
                'domain' => $request->input('domain'),
                'phone' => $request->input('phone'),
                'source' => $request->string('source')->toString(),
                'notes' => $request->input('notes'),
            ], true, false, null);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'status' => 'failed',
                'message' => 'Não foi possível processar o pedido comercial.',
                'error' => app()->hasDebugModeEnabled() ? $exception->getMessage() : null,
            ], 500);
        }

        $accepted = ($report['status'] ?? null) === 'awaiting_approval';

        return response()->json([
            'ok' => $accepted,
            'status' => $report['status'] ?? null,
            'commercial_status' => $report['commercial_status'] ?? null,
            'project_slug' => $report['project_slug'] ?? null,
            'persisted' => $report['persisted'] ?? false,
            'report_path' => $report['path'] ?? null,
            'message' => $accepted
                ? 'Pedido analisado e aguardando aprovação operacional.'
                : 'A Factory não aceitou o pedido para análise.',
        ], $accepted ? 202 : 422);
    }
}

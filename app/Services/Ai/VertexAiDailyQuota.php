<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class VertexAiDailyQuota
{
    public function reserve(string $project, int $limit): void
    {
        if ($project === '' || $limit < 1) {
            throw new RuntimeException('Limite diário Vertex não configurado.');
        }

        $identity = ['project' => $project, 'usage_date' => now('UTC')->toDateString()];
        try {
            DB::table('vertex_ai_daily_usage')->insertOrIgnore($identity + ['requests' => 0]);

            // Conditional increment is atomic across workers sharing this database.
            $reserved = DB::table('vertex_ai_daily_usage')->where($identity)
                ->where('requests', '<', $limit)->increment('requests');
        } catch (Throwable) {
            throw new RuntimeException('Controle de consumo Vertex indisponível; geração bloqueada.');
        }

        if ($reserved !== 1) {
            throw new RuntimeException('Limite diário Vertex atingido.');
        }
        // Reservations are retained after HTTP errors/timeouts: upstream may have accepted the request.
    }
}

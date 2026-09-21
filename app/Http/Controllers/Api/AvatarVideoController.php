<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeygenAvatar;
use App\Models\HeygenVideoJob;
use App\Services\Heygen\HeygenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvatarVideoController extends Controller
{
    public function store(Request $request, HeygenService $service): JsonResponse
    {
        $expected = trim((string) config('centro_ia.internal_token', ''));
        $received = trim((string) $request->bearerToken());

        if ($expected === '' || $received === '' || ! hash_equals($expected, $received)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $data = $request->validate([
            'event_id' => ['required', 'string', 'max:160'],
            'project_id' => ['required', 'string', 'max:120'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'title' => ['nullable', 'string', 'max:180'],
            'script' => ['required', 'string', 'min:1', 'max:120000'],
            'avatar_id' => ['nullable', 'string', 'max:255'],
            'voice_id' => ['nullable', 'string', 'max:255'],
        ]);

        $projectHeader = trim((string) $request->header('X-Vitrine-Project', ''));
        if ($projectHeader === '' || ! hash_equals((string) $data['project_id'], $projectHeader)) {
            return response()->json(['ok' => false, 'error' => 'project_identity_mismatch'], 422);
        }

        $avatar = null;
        if (! empty($data['avatar_id'])) {
            $avatar = HeygenAvatar::query()->firstOrCreate(
                ['avatar_id' => $data['avatar_id']],
                [
                    'name' => 'Flow avatar '.$data['avatar_id'],
                    'voice_id' => $data['voice_id'] ?? null,
                    'status' => 'Ativo',
                ]
            );

            if (! empty($data['voice_id']) && $avatar->voice_id !== $data['voice_id']) {
                $avatar->update(['voice_id' => $data['voice_id']]);
            }
        }

        $job = HeygenVideoJob::query()->create([
            'company_id' => $data['company_id'] ?? null,
            'heygen_avatar_id' => $avatar?->id,
            'title' => $data['title'] ?? 'Vídeo Vitrine IA Pro',
            'status' => 'Pendente',
            'script' => $data['script'],
            'payload' => json_encode([
                'event_id' => $data['event_id'],
                'project_id' => $data['project_id'],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $job = $service->generateVideo($job);

        return response()->json([
            'ok' => $job->status !== 'Erro',
            'event_id' => $data['event_id'],
            'job_id' => $job->id,
            'provider' => 'heygen',
            'status' => $job->status,
            'video_id' => $job->heygen_video_id,
            'error' => $job->status === 'Erro' ? $job->error_message : null,
        ], $job->status === 'Erro' ? 502 : 202);
    }
}

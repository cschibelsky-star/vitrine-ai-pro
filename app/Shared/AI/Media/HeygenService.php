<?php

declare(strict_types=1);

namespace App\Shared\AI\Media;

use App\Models\HeygenVideoJob;
use App\Shared\AI\Video\VideoProvider;
use App\Shared\AI\Video\VideoRequest;
use App\Shared\AI\Video\VideoSession;
use App\Shared\AI\Video\VideoVersion;
use InvalidArgumentException;

/**
 * Serviço canônico de mídia IA via HeyGen.
 *
 * Mantém a API legada intacta (generateVideo/refreshStatus/callback) e também
 * expõe o contrato compartilhado do Vitrine Video Engine. A ponte somente
 * executa geração quando um HeygenVideoJob persistido é informado
 * explicitamente em metadata. Assim, a adoção do novo contrato não cria
 * consumo de créditos por efeito colateral.
 */
class HeygenService extends \App\Services\Heygen\HeygenService implements VideoProvider
{
    public function providerId(): string
    {
        return 'heygen';
    }

    public function supports(VideoRequest $request): bool
    {
        return isset($request->metadata['heygen_job_id'])
            && is_numeric($request->metadata['heygen_job_id']);
    }

    public function generate(VideoRequest $request, VideoSession $session): VideoVersion
    {
        if (! $this->supports($request)) {
            throw new InvalidArgumentException('HeyGen requires a persisted heygen_job_id for execution.');
        }

        $job = HeygenVideoJob::query()->findOrFail((int) $request->metadata['heygen_job_id']);
        $job = $this->generateVideo($job);

        return new VideoVersion(
            versionId: 'HEYGEN-'.$job->id,
            sessionId: $session->sessionId,
            number: count($session->versions) + 1,
            aspectRatio: $request->aspectRatios[0] ?? '16:9',
            status: (string) $job->status,
            provider: $this->providerId(),
            providerJobId: $job->heygen_video_id ? (string) $job->heygen_video_id : (string) $job->id,
            videoUrl: $job->video_url ? (string) $job->video_url : null,
            metadata: ['heygen_job_id' => $job->id],
        );
    }
}

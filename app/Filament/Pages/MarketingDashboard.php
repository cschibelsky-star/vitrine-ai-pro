<?php

namespace App\Filament\Pages;

use App\Marketing\Application\MarketingDashboardStateReader;
use App\Marketing\Application\VideoFinalizationService;
use App\Marketing\Domain\Agents\AgentRegistry;
use App\Models\AiAgent;
use App\Models\AiProvider;
use App\Services\Ai\AiMediaGenerationService;
use App\Marketing\Domain\Video\VideoProject;
use App\Marketing\Infrastructure\Video\GeminiVeoSceneRenderer;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Throwable;

class MarketingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = '10 · IA Center';
    protected static ?string $navigationLabel = 'Marketing IA';
    protected static ?string $title = 'Marketing IA';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.marketing-dashboard-exact';

    public string $copilotMessage = '';
    public string $copilotSessionId = '';
    public array $copilotMessages = [];
    public array $copilotArchives = [];
    public ?string $copilotActiveArchiveId = null;
    public ?string $copilotError = null;

    #[\Livewire\Attributes\Locked]
    public string $marketingContextKey = 'tv_sumare_client';

    public string $flowCampaign = 'TV Sumaré';
    public string $flowObjective = '';
    public string $flowAudience = 'Pequenos negócios, criadores e influenciadores';
    public string $flowFormat = 'reel_9_16';
    public string $flowDuration = '8 segundos';
    public string $flowMessage = '';
    public string $flowCta = '';
    public string $flowStyle = 'Tecnológico, premium, humano e direto';
    public string $flowPackage = '';
    public ?string $flowError = null;

    public string $flowToolName = 'Vitrine Content Studio';
    public string $flowToolUrl = '';
    public string $flowProjectName = 'Vitrine Social Mídia';
    public string $flowJobId = '';
    public string $flowJobStatus = 'RASCUNHO';
    public string $flowGenerationSource = '';
    #[\Livewire\Attributes\Locked]
    public array $flowJobs = [];

    public string $nativeProductionStatus = 'RASCUNHO';
    public string $nativeProductionJobRef = '';
    public string $nativeProductionAssetUrl = '';
    public string $nativeProductionFinalPath = '';
    public string $nativeProductionPreviewUrl = '';
    public ?string $nativeProductionError = null;


    public array $pieceRevisionInputs = [];
    public array $pieceScheduleInputs = [];
    public ?string $pieceFeedback = null;
    public ?string $pieceError = null;

    private function pieceVersion(array $job): string
    {
        return hash('sha256', json_encode([
            $job['id'] ?? '', $job['revision_count'] ?? 0,
            $job['asset_url'] ?? '', $job['provider_job_ref'] ?? '',
            $job['final_path'] ?? '', $job['director_job'] ?? [],
        ], JSON_THROW_ON_ERROR));
    }

    private function galleryDirectory(): string
    {
        abort_unless(auth()->check(), 403);
        $scope = auth()->id().'|'.(string) auth()->user()?->company_id.'|'.$this->marketingContextKey;
        return 'marketing/gallery/'.hash('sha256', $scope);
    }

    private function saveGalleryPiece(array &$job): void
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $directory = $this->galleryDirectory();
        $path = $directory.'/'.hash('sha256', (string) $job['id']).'.json';
        Cache::lock('marketing-gallery:'.hash('sha256', $path), 10)->block(5, function () use ($disk, $directory, $path, &$job): void {
            if ($disk->exists($path)) {
                $stored = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
                if ($stored === $job) {
                    return;
                }
                if (($stored['_gallery_etag'] ?? null) !== ($job['_gallery_etag'] ?? null)) {
                    throw new \RuntimeException('A peça foi atualizada em outra janela. Atualize antes de continuar.');
                }
            }
            $disk->makeDirectory($directory);
            $job['_gallery_etag'] = bin2hex(random_bytes(16));
            $target = $disk->path($path);
            $temporary = tempnam(dirname($target), '.piece-');
            if ($temporary === false) {
                throw new \RuntimeException('Não foi possível salvar a peça.');
            }
            try {
                $encoded = json_encode($job, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                if (file_put_contents($temporary, $encoded) === false || ! rename($temporary, $target)) {
                    throw new \RuntimeException('Não foi possível salvar a peça.');
                }
            } finally {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
        });
    }

    private function storedProductionPieces(): array
    {
        if (! auth()->check()) {
            return [];
        }
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $jobs = [];
        foreach ($disk->files($this->galleryDirectory()) as $path) {
            if (str_ends_with($path, '.json')) {
                $job = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($job) && ! empty($job['id'])) {
                    $jobs[] = $job;
                }
            }
        }
        usort($jobs, static fn (array $a, array $b): int => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));
        return $jobs;
    }

    private function mergeStoredProductionPieces(array $sessionJobs): array
    {
        $jobs = [];
        foreach ($sessionJobs as $job) {
            $jobs[(string) ($job['id'] ?? '')] = $job;
        }
        foreach ($this->storedProductionPieces() as $job) {
            $jobs[(string) $job['id']] = $job;
        }
        return array_values($jobs);
    }

    public function hydrate(): void
    {
        $state = (array) session('marketing_workstation.production.'.$this->marketingContextKey, []);
        $this->flowJobs = $this->mergeStoredProductionPieces((array) ($state['jobs'] ?? []));
    }

    public function getGalleryJobs(): array
    {
        if (! auth()->check()) {
            return [];
        }
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $jobs = [];
        foreach ($disk->files($this->galleryDirectory()) as $path) {
            if (! str_ends_with($path, '.json')) {
                continue;
            }
            $job = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
            if (is_array($job) && in_array($job['status'] ?? '', ['APROVADO', 'PLANEJADO_EDITORIAL'], true)
                && hash_equals((string) ($job['approved_version'] ?? ''), $this->pieceVersion($job))) {
                if (preg_match('/^IMAGE-(\d+)$/', (string) ($job['provider_job_ref'] ?? ''), $match)) {
                    $job['preview_url'] = URL::temporarySignedRoute('marketing.native-image-preview',
                        now()->addHours(2), ['generation' => (int) $match[1]], false);
                }
                $jobs[] = $job;
            }
        }
        usort($jobs, static fn (array $a, array $b): int => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));
        return $jobs;
    }

    private function locatePiece(string $jobId, string $version): ?int
    {
        $this->pieceError = null;
        $this->pieceFeedback = null;
        $state = (array) session('marketing_workstation.production.'.$this->marketingContextKey, []);
        $this->flowJobs = $this->normalizeProductionJobs($this->mergeStoredProductionPieces((array) ($state['jobs'] ?? [])));
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $path = $this->galleryDirectory().'/'.hash('sha256', $jobId).'.json';
        $stored = $disk->exists($path) ? json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR) : null;
        foreach ($this->flowJobs as $index => $job) {
            if (($job['id'] ?? '') === $jobId) {
                if (is_array($stored)) {
                    $job = $stored;
                    $this->flowJobs[$index] = $job;
                }
                if (! hash_equals($this->pieceVersion($job), $version)) {
                    $this->pieceError = 'A peça mudou. Atualize e revise a versão atual.';
                    return null;
                }
                return $index;
            }
        }
        foreach ($this->getGalleryJobs() as $job) {
            if (($job['id'] ?? '') === $jobId && hash_equals($this->pieceVersion($job), $version)) {
                $this->flowJobs[] = $job;
                return array_key_last($this->flowJobs);
            }
        }
        $this->pieceError = 'Peça não encontrada neste contexto. Atualize a página.';
        return null;
    }

    public function getPieceVersion(array $job): string
    {
        return $this->pieceVersion($job);
    }

    public function approveProductionJob(string $jobId, string $version): void
    {
        $index = $this->locatePiece($jobId, $version);
        if ($index === null) {
            return;
        }
        $job = $this->flowJobs[$index];
        if (! in_array($job['status'] ?? '', ['EM_QA', 'GERADO'], true)
            || (empty($job['asset_url']) && empty($job['preview_url']))) {
            $this->pieceError = 'A peça precisa estar concluída e disponível para revisão antes da aprovação.';
            return;
        }
        $job['status'] = 'APROVADO';
        $job['approved_version'] = $this->pieceVersion($job);
        $job['approved_at'] = now()->toISOString();
        $job['approved_by'] = auth()->id();
        $job['updated_at'] = now()->toISOString();
        $job['scheduled_at'] = null;
        $job['publication_status'] = 'NOT_REQUESTED';
        $this->saveGalleryPiece($job);
        $this->flowJobs[$index] = $job;
        $this->persistFlowWorkstation();
        $this->pieceFeedback = 'Versão aprovada e salva na galeria. Escolha quando distribuir.';
    }

    public function requestPieceRevision(string $jobId, string $version): void
    {
        $index = $this->locatePiece($jobId, $version);
        if ($index === null) {
            return;
        }
        $instruction = trim((string) ($this->pieceRevisionInputs[$jobId] ?? ''));
        if (mb_strlen($instruction) < 3 || mb_strlen($instruction) > 2000) {
            $this->pieceError = 'Descreva o ajuste em 3 a 2.000 caracteres.';
            return;
        }
        if (! in_array($this->flowJobs[$index]['status'] ?? '', ['EM_QA', 'GERADO', 'APROVADO', 'PLANEJADO_EDITORIAL', 'REPROVADO_QA', 'ERRO'], true)) {
            $this->pieceError = 'Aguarde a produção desta peça antes de solicitar outra revisão.';
            return;
        }
        $this->reviseProductionJobFromDirector($instruction, '', $jobId);
        $this->persistCopilot();
        $this->pieceRevisionInputs[$jobId] = '';
        if (($this->flowJobs[$index]['status'] ?? '') === 'ERRO') {
            $this->pieceError = 'A correção foi registrada, mas a geração falhou. A versão anterior foi preservada e a aprovação foi retirada.';
        } else {
            $this->pieceFeedback = 'A revisão foi vinculada somente à peça selecionada. A nova versão exigirá aprovação.';
        }
    }

    public function planPiecePublication(string $jobId, string $version): void
    {
        $index = $this->locatePiece($jobId, $version);
        if ($index === null) {
            return;
        }
        $job = $this->flowJobs[$index];
        if (! in_array($job['status'] ?? '', ['APROVADO', 'PLANEJADO_EDITORIAL'], true)
            || ! hash_equals((string) ($job['approved_version'] ?? ''), $this->pieceVersion($job))) {
            $this->pieceError = 'Aprove a versão atual antes de planejar sua publicação.';
            return;
        }
        $raw = trim((string) ($this->pieceScheduleInputs[$jobId] ?? ''));
        try {
            $date = \Carbon\CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $raw, 'America/Sao_Paulo');
            if (! $date || $date->format('Y-m-d\\TH:i') !== $raw || ! $date->isFuture()) {
                throw new \InvalidArgumentException();
            }
        } catch (Throwable) {
            $this->pieceError = 'Informe uma data futura válida no horário de Brasília.';
            return;
        }
        $job['status'] = 'PLANEJADO_EDITORIAL';
        $job['scheduled_at'] = $date->utc()->toISOString();
        $job['schedule_timezone'] = 'America/Sao_Paulo';
        $job['publication_status'] = 'PUBLISHER_NOT_CONNECTED';
        $job['updated_at'] = now()->toISOString();
        $this->saveGalleryPiece($job);
        $this->flowJobs[$index] = $job;
        $this->persistFlowWorkstation();
        $this->pieceFeedback = 'Data salva no calendário editorial. A publicação automática ainda depende da conta publicadora.';
    }

    public function keepPieceInGallery(string $jobId, string $version): void
    {
        $index = $this->locatePiece($jobId, $version);
        if ($index === null) {
            return;
        }
        $job = $this->flowJobs[$index];
        if (! in_array($job['status'] ?? '', ['APROVADO', 'PLANEJADO_EDITORIAL'], true)
            || ! hash_equals((string) ($job['approved_version'] ?? ''), $this->pieceVersion($job))) {
            $this->pieceError = 'Aprove a versão atual para mantê-la na galeria.';
            return;
        }
        $job['status'] = 'APROVADO';
        $job['scheduled_at'] = null;
        $job['publication_status'] = 'NOT_REQUESTED';
        $job['updated_at'] = now()->toISOString();
        $this->saveGalleryPiece($job);
        $this->flowJobs[$index] = $job;
        $this->persistFlowWorkstation();
        $this->pieceFeedback = 'Peça mantida na galeria, sem data de publicação.';
    }

    public function publishPieceNow(string $jobId, string $version): void
    {
        $index = $this->locatePiece($jobId, $version);
        if ($index === null) {
            return;
        }
        $job = $this->flowJobs[$index];
        if (! in_array($job['status'] ?? '', ['APROVADO', 'PLANEJADO_EDITORIAL'], true)
            || ! hash_equals((string) ($job['approved_version'] ?? ''), $this->pieceVersion($job))) {
            $this->pieceError = 'Aprove a versão atual antes de publicar.';
            return;
        }
        $this->pieceError = 'Publicação indisponível: a conta publicadora e o envio direto ainda não estão integrados. A peça continua salva; nada foi publicado.';
    }

    public function mount(): void
    {
        $availableContexts = array_keys((array) config('marketing_agents.contexts', []));
        $storedContext = (string) session('marketing_context.key', $this->marketingContextKey);
        $this->marketingContextKey = in_array($storedContext, $availableContexts, true) ? $storedContext : 'tv_sumare_client';

        $copilotSessionKey = 'marketing_copilot.'.$this->marketingContextKey;
        $this->copilotSessionId = (string) session($copilotSessionKey.'.session_id', 'MKT-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(3))));
        $this->copilotMessages = array_values((array) session($copilotSessionKey.'.messages', []));
        $this->copilotArchives = array_values((array) session($copilotSessionKey.'.archives', []));
        $this->copilotActiveArchiveId = session($copilotSessionKey.'.active_archive_id');
        $legacyCleanupDone = (bool) session($copilotSessionKey.'.legacy_cleanup_done', false);

        if (! $legacyCleanupDone && $this->copilotMessages !== []) {
            $archiveId = 'ARCH-LEGACY-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(2)));
            $this->copilotArchives = collect($this->copilotArchives)
                ->prepend([
                    'id' => $archiveId,
                    'campaign' => 'Histórico legado',
                    'message' => '',
                    'director_reply' => '',
                    'summary' => 'Conversa anterior preservada durante a limpeza do chat ativo.',
                    'jobs' => [],
                    'messages' => $this->copilotMessages,
                    'created_at' => now()->toISOString(),
                ])
                ->take(20)
                ->values()
                ->all();
            $this->copilotMessages = [];
            $this->copilotMessage = '';
            $this->copilotActiveArchiveId = null;
            $this->copilotSessionId = 'MKT-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(3)));
            session([$copilotSessionKey.'.legacy_cleanup_done' => true]);
        }

        $this->persistCopilot();

        $productionState = (array) session('marketing_workstation.production.'.$this->marketingContextKey, []);
        $legacyState = [];
        $workstation = $productionState !== [] ? $productionState : $legacyState;

        $this->flowCampaign = (string) ($workstation['campaign'] ?? $this->flowCampaign);
        $this->flowObjective = (string) ($workstation['objective'] ?? '');
        $this->flowAudience = (string) ($workstation['audience'] ?? $this->flowAudience);
        $this->flowFormat = (string) ($workstation['format'] ?? $this->flowFormat);
        $this->flowDuration = (string) ($workstation['duration'] ?? $this->flowDuration);
        $this->flowMessage = (string) ($workstation['message'] ?? '');
        $this->flowCta = (string) ($workstation['cta'] ?? '');
        $this->flowStyle = (string) ($workstation['style'] ?? $this->flowStyle);
        $this->flowPackage = (string) ($workstation['package'] ?? '');
        $this->flowToolName = (string) ($workstation['tool_name'] ?? $this->flowToolName);
        $this->flowToolUrl = (string) ($workstation['tool_url'] ?? '');
        $this->flowProjectName = (string) ($workstation['project_name'] ?? $this->flowProjectName);
        $this->flowJobId = (string) ($workstation['job_id'] ?? '');
        $this->flowJobStatus = (string) ($workstation['job_status'] ?? 'RASCUNHO');
        $this->flowGenerationSource = (string) ($workstation['generation_source'] ?? '');
        $this->flowJobs = $this->normalizeProductionJobs($this->mergeStoredProductionPieces(array_values((array) ($workstation['jobs'] ?? []))));
        $this->nativeProductionStatus = (string) ($workstation['native_status'] ?? 'RASCUNHO');
        $this->nativeProductionJobRef = (string) ($workstation['native_job_ref'] ?? '');
        $this->nativeProductionAssetUrl = (string) ($workstation['native_asset_url'] ?? '');
        $this->nativeProductionFinalPath = (string) ($workstation['native_final_path'] ?? '');
        $this->nativeProductionPreviewUrl = (string) ($workstation['native_preview_url'] ?? '');
        $this->nativeProductionError = null;

        if (str_starts_with($this->flowJobId, 'FLOW-')) {
            $this->flowJobId = '';
            $this->flowJobStatus = 'RASCUNHO';
            $this->nativeProductionStatus = 'RASCUNHO';
            $this->nativeProductionJobRef = '';
            $this->nativeProductionAssetUrl = '';
            $this->nativeProductionFinalPath = '';
            $this->nativeProductionPreviewUrl = '';
        }

        if ($productionState === [] && $legacyState !== []) {
            $this->persistFlowWorkstation();
            session()->forget('marketing_workstation.flow');
        }

        $nativeStudio = (array) config('marketing_agents.native_studio', []);
        $context = $this->getMarketingContext();
        $this->flowToolName = 'Marketing IA Native Studio';
        $this->flowProjectName = trim((string) ($context['brand'] ?? $nativeStudio['official_project_name'] ?? 'Vitrine Social Mídia')) ?: 'Vitrine Social Mídia';

        if ($productionState === []) {
            $this->flowCampaign = (string) ($context['brand'] ?? 'TV Sumaré');
            $this->flowAudience = ($context['mode'] ?? 'client') === 'engine'
                ? 'Audiência editorial da TV Digital e público do veículo atendido'
                : 'Moradores de Sumaré e região, audiência local e comunidade';
            $this->flowStyle = ($context['mode'] ?? 'client') === 'engine'
                ? 'Jornalístico, claro, confiável e adaptado à identidade do veículo'
                : 'Local, jornalístico, humano, próximo da comunidade e visualmente consistente com a TV Sumaré';
        }
    }

    public function sendCopilotMessage(): void
    {
        $message = trim($this->copilotMessage);
        $this->copilotError = null;

        if ($message === '') {
            return;
        }

        if (mb_strlen($message) > 4000) {
            $this->copilotError = 'A mensagem deve ter no máximo 4.000 caracteres.';
            return;
        }

        $history = array_slice($this->copilotMessages, -12);
        $this->copilotMessages[] = ['role' => 'user', 'content' => $message, 'at' => now()->toISOString()];
        $this->syncProductionBriefFromText($message);
        $this->copilotMessage = '';

        try {
            $hub = (array) config('marketing_agents.hub', []);
            $url = trim((string) ($hub['url'] ?? ''));
            $token = trim((string) ($hub['token'] ?? ''));
            $projectId = trim((string) ($hub['project_id'] ?? 'vitrine-marketing-agents-core'));
            $capability = trim((string) ($hub['capability'] ?? 'marketing_generation'));

            $historyText = collect($history)
                ->map(static fn (array $item): string => strtoupper((string) ($item['role'] ?? 'user')).': '.(string) ($item['content'] ?? ''))
                ->implode("\n\n");

            $marketingContext = $this->getMarketingContext();
            $contextInstruction = ($marketingContext['mode'] ?? 'client') === 'engine'
                ? 'CONTEXTO OPERACIONAL: MOTOR TV DIGITAL. Você está atuando como capacidade interna do produto TV Digital Enterprise. Não trate a TV Sumaré como cliente de marketing neste contexto. Trabalhe somente em funções de apoio editorial, transformação de conteúdo, vídeo, criativos e distribuição vinculadas ao produto TV Digital. '
                : 'CONTEXTO OPERACIONAL: CLIENTE TV SUMARÉ. Você está atendendo a marca TV Sumaré como cliente independente do Marketing IA. Crie estratégia e conteúdo para as redes sociais da TV Sumaré, incluindo conteúdos próprios de marca, comunidade, agenda, curiosidades, engajamento, bastidores e campanhas. Notícias do portal podem ser matéria-prima, mas não são a única origem. Não confunda este contexto com o motor interno da TV Digital. ';

            $system = $contextInstruction.'Você é o Diretor de Marketing IA da Vitrine IA Pro dentro do Centro Operacional de Marketing. '
                .'Atue como copiloto operacional, em português do Brasil. Organize estratégia, campanha, copy, criativos, vídeo, distribuição e QA. '
                .'Para mídia, use o Centro IA e seu roteamento dinâmico; não fixe Veo, Grok, Seedream, Seedance ou outro modelo. HeyGen só deve ser proposto quando o pedido exigir explicitamente o avatar de Cristian Schibelsky e sua voz clonada como apresentador do Vitrine Social Mídia. '
                .'Nunca afirme que publicou, agendou, ativou campanha ou gastou verba sem uma ação operacional confirmada. '
                .'Após aprovação, o cliente escolhe guardar na galeria, publicar ou agendar nas contas cadastradas. Metricool é uma evolução futura; nunca prometa publicação sem confirmação do publicador. '
                .'Mídia paga deve ir ao Windsor.ai FB Ads/Meta Ads somente após aprovação humana e autorização explícita de orçamento/ativação. '
                .'Não invente preços, clientes, depoimentos, métricas ou funcionalidades.';

            $userPrompt = $historyText === '' ? $message : "Histórico recente:\n{$historyText}\n\nNova mensagem:\n{$message}";

            $reply = '';
            $model = '';
            $executionId = null;

            if ($url !== '' && $token !== '') {
                $response = Http::acceptJson()
                    ->asJson()
                    ->withToken($token)
                    ->withHeaders(['X-Vitrine-Project' => $projectId])
                    ->timeout(max(1, min((int) ($hub['timeout'] ?? 60), 120)))
                    ->retry(2, 250, throw: false)
                    ->post($url, [
                        'project_id' => $projectId,
                        'capability' => $capability,
                        'input' => [
                            'system' => $system,
                            'user' => $userPrompt,
                            'response_format' => 'text',
                            'temperature' => 0.3,
                        ],
                    ]);

                if ($response->successful() && $response->json('ok')) {
                    $reply = trim((string) $response->json('output_text'));
                    $model = (string) ($response->json('model') ?: 'hub-routed');
                    $executionId = $response->json('execution_id');
                } else {
                    logger()->warning('Diretor Marketing IA: Centro IA indisponível; acionando Gemini direto.', [
                        'http_status' => $response->status(),
                        'error' => (string) ($response->json('error') ?? 'unknown'),
                        'capability' => $capability,
                    ]);
                }
            }

            if ($reply === '') {
                $reply = $this->generateFlowPackageWithGemini($system, $userPrompt);
                $model = (string) config('marketing_agents.native_studio.director_model', 'gemini-3.5-flash').' (fallback direto)';
                $executionId = null;
            }

            $this->copilotMessages[] = [
                'role' => 'assistant',
                'content' => $reply,
                'at' => now()->toISOString(),
                'model' => $model,
                'execution_id' => $executionId,
            ];

            if ($this->isRevisionRequest($message)) {
                $this->reviseProductionJobFromDirector($message, $reply);
            } elseif ($this->shouldAutoProduceFromDirector($message)) {
                $this->autoProduceDirectorCampaign($message, $reply);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->copilotError = $exception->getMessage();
        }

        $this->copilotMessages = array_slice($this->copilotMessages, -30);
        $this->persistCopilot();
    }

    public function generateFlowPackage(): void
    {
        $this->flowError = null;

        $campaign = trim($this->flowCampaign);
        $objective = trim($this->flowObjective);
        $audience = trim($this->flowAudience);
        $message = trim($this->flowMessage);
        $cta = trim($this->flowCta);
        $style = trim($this->flowStyle);

        if ($campaign === '' || $objective === '' || $message === '') {
            $this->flowError = 'Informe campanha, objetivo e mensagem principal.';
            return;
        }

        $formats = [
            'reel_9_16' => 'Reel vertical 9:16',
            'story_9_16' => 'Story vertical 9:16',
            'video_16_9' => 'Vídeo horizontal 16:9',
            'ad_1_1' => 'Criativo quadrado 1:1',
        ];

        if (! isset($formats[$this->flowFormat])) {
            $this->flowError = 'Formato de criação inválido.';
            return;
        }

        try {
            $hub = (array) config('marketing_agents.hub', []);
            $url = trim((string) ($hub['url'] ?? ''));
            $token = trim((string) ($hub['token'] ?? ''));
            $projectId = trim((string) ($hub['project_id'] ?? 'vitrine-marketing-agents-core'));
            $capability = trim((string) ($hub['capability'] ?? 'marketing_generation'));

            $context = $this->getMarketingContext();
            $contextInstruction = ($context['mode'] ?? 'client') === 'engine'
                ? 'CONTEXTO OPERACIONAL: MOTOR TV DIGITAL. Produza ativos como capacidade interna da TV Digital Enterprise, preservando a identidade do veículo atendido e sem tratar a TV Sumaré como cliente de marketing neste contexto. '
                : 'CONTEXTO OPERACIONAL: CLIENTE TV SUMARÉ. Produza conteúdo para as redes sociais e presença digital da marca TV Sumaré como cliente independente do Marketing IA. O conteúdo pode ser próprio de marca, comunidade, agenda, curiosidades, engajamento, bastidores ou derivado de notícias, sem depender exclusivamente do portal. ';

            $creationDirectives = $this->creationDirectiveText(['marketing_briefing', 'creative_direction', 'brand_asset_guard', 'marketing_qa']);

            $system = $contextInstruction.'Você é o Creative Director do Fluxo de Produção nativo do Marketing IA da Vitrine IA Pro. '
                .'Sua função é preparar um Job de Produção executável pelos motores nativos do Marketing IA e pela finalização técnica. '
                .'DIRETRIZES NORMATIVAS: '.$creationDirectives.' '
                .'POLÍTICA DE ROTEAMENTO: não escolha nem fixe Gemini, Veo, Grok, Seedream, Seedance ou qualquer outro modelo/provedor. Descreva a necessidade criativa e deixe a escolha tecnológica exclusivamente para o Centro IA. HeyGen só pode ser usado para jobs de apresentação quando o briefing pedir explicitamente avatar e voz autorizados. '
                .'Não afirme que gerou mídia, publicou ou consumiu créditos sem execução operacional confirmada. '
                .'Entregue um briefing implementável e objetivo, em português do Brasil, preservando fatos fornecidos e sem inventar logos, preços, depoimentos ou funcionalidades. '
                .'Quando a campanha ou produto for Vitrine Social Mídia, a comunicação deve deixar explícito que o assunto é redes sociais, produção de conteúdo, calendário editorial, Instagram/Facebook ou presença digital. Não use metáforas ambíguas como "vitrine parada", "vitrine estagnada" ou equivalentes sem explicar imediatamente que se trata das redes sociais. '
                .'O CTA deve ser exatamente o informado no briefing; se estiver vazio, marque CTA COMO NECESSÁRIO em vez de inventar "Assine agora" ou outra chamada. '
                .'Não gere, redesenhe nem interprete logotipos. Em ASSETS NECESSÁRIOS, sempre registre o logo oficial da marca ativa no contexto. Se o logo precisar fazer parte da cena, exija o arquivo oficial como imagem de referência; para assinatura de marca/watermark, indique aplicação em pós-produção pelo Marketing IA. '
                .'Não inclua marcas, logotipos ou produtos identificáveis de terceiros sem que tenham sido fornecidos como asset autorizado. Evite texto duplicado e determine uma única ocorrência por mensagem na tela. '
                .'Estruture obrigatoriamente em: JOB DE PRODUÇÃO, DIREÇÃO CRIATIVA, CENA 01, CENA 02 quando necessária, CÂMERA, ÁUDIO, TEXTO NA TELA, NEGATIVE PROMPT, ASSETS NECESSÁRIOS e QA CHECKLIST. '
                .'No QA CHECKLIST, valide clareza sobre redes sociais, ausência de texto duplicado, ausência de marcas de terceiros, CTA fiel ao briefing e uso do logo oficial somente por asset/pós-produção. '
                .'Os prompts visuais devem estar prontos para execução pelos motores nativos do Marketing IA e devem respeitar o formato solicitado.';

            $userPrompt = "Campanha: {$campaign}\n"
                ."Objetivo: {$objective}\n"
                ."Público: {$audience}\n"
                ."Formato: {$formats[$this->flowFormat]}\n"
                ."Duração desejada: {$this->flowDuration}\n"
                ."Mensagem principal: {$message}\n"
                ."CTA: {$cta}\n"
                ."Estilo: {$style}\n\n"
                .'Prepare o Job de Produção para execução nativa no Marketing IA. Se faltar algum asset de marca, marque como necessário em vez de inventar. '
                .'Para Vitrine Social Mídia, deixe evidente que o problema e a solução dizem respeito às redes sociais e à operação de conteúdo. '
                .'O logo oficial da marca ativa será fornecido/aplicado pelo Marketing IA; não peça ao gerador para recriá-lo.';

            $package = '';
            $this->flowGenerationSource = '';

            if ($url !== '' && $token !== '') {
                $response = Http::acceptJson()
                    ->asJson()
                    ->withToken($token)
                    ->withHeaders(['X-Vitrine-Project' => $projectId])
                    ->timeout(max(1, min((int) ($hub['timeout'] ?? 60), 120)))
                    ->retry(2, 250, throw: false)
                    ->post($url, [
                        'project_id' => $projectId,
                        'capability' => $capability,
                        'input' => [
                            'system' => $system,
                            'user' => $userPrompt,
                            'response_format' => 'text',
                            'temperature' => 0.25,
                        ],
                    ]);

                if ($response->successful() && $response->json('ok')) {
                    $package = trim((string) $response->json('output_text'));
                    if ($package !== '') {
                        $this->flowGenerationSource = 'Centro IA';
                    }
                } else {
                    logger()->warning('Fluxo Marketing IA: Centro IA indisponível; acionando fallback Gemini local.', [
                        'http_status' => $response->status(),
                        'capability' => $capability,
                    ]);
                }
            }

            if ($package === '') {
                $package = $this->generateFlowPackageWithGemini($system, $userPrompt);
                $this->flowGenerationSource = 'Gemini direto (fallback)';
            }

            if ($package === '') {
                throw new \RuntimeException('Não foi possível gerar o Job de Produção do Marketing IA.');
            }

            $this->flowPackage = $package;
            $this->createFlowJob('PREPARADO');
            $this->persistFlowWorkstation();

            // Jobs criados pelo Creative Studio seguem direto para o motor nativo.
            // O botão Gerar mídia permanece apenas como fallback/retry em caso de falha.
            $this->startNativeProduction();
        } catch (Throwable $exception) {
            report($exception);
            $this->flowError = $exception->getMessage();
        }
    }

    public function startNativeProduction(): void
    {
        $this->nativeProductionError = null;
        $this->nativeProductionAssetUrl = '';
        $this->nativeProductionFinalPath = '';
        $this->nativeProductionPreviewUrl = '';

        if ($this->flowJobId === '' || $this->flowPackage === '') {
            $this->nativeProductionError = 'Gere primeiro o Job de Produção do Marketing IA.';
            return;
        }

        if ($this->flowFormat === 'ad_1_1') {
            $this->startNativeImageProduction();
            return;
        }

        $aspectRatio = $this->flowFormat === 'video_16_9' ? '16:9' : '9:16';
        $duration = $this->nativeDurationSeconds();
        $activeContext = $this->getMarketingContext();
        $activeBrand = trim((string) ($activeContext['brand'] ?? ''));
        $campaignName = trim($this->flowCampaign);
        $subject = $campaignName !== '' ? $campaignName : ($activeBrand !== '' ? $activeBrand : 'a campanha ativa');

        $prompt = 'Crie um vídeo publicitário profissional para '.$subject.'. '
            .'Marca/contexto: '.($activeBrand !== '' ? $activeBrand : 'marca ativa do briefing').'. '
            .'Público: '.trim($this->flowAudience).'. '
            .'Objetivo: '.trim($this->flowObjective).'. '
            .'Mensagem que a narrativa visual deve comunicar: '.trim($this->flowMessage).'. '
            .'Direção visual: '.trim($this->flowStyle).'. '
            .'Mostre de forma clara uma rotina real de produção e gestão de conteúdo para redes sociais. '
            .'Gere somente a base visual limpa: não renderize palavras, legendas, CTA, logotipos, marcas de terceiros ou watermark. '
            .'Preserve áreas seguras para headline, mensagem, CTA e logo que serão aplicados deterministicamente pelo Marketing IA na finalização técnica.';

        try {
            $project = new VideoProject(
                projectId: $this->flowJobId,
                productId: 'vitrine-social-midia',
                campaignId: ((string) str($this->flowCampaign)->slug()) ?: 'marketing-ia',
            );
            $scene = $project->addScene('SCENE-01', 1, ['prompt' => $prompt]);

            $job = app(GeminiVeoSceneRenderer::class)->dispatch($project, $scene, [
                'aspect_ratio' => $aspectRatio,
                'duration_seconds' => $duration,
                'resolution' => $duration === 8 ? '1080p' : '720p',
            ]);

            $this->nativeProductionJobRef = (string) ($job['job_ref'] ?? '');
            $this->nativeProductionStatus = 'EM_GERACAO';
            $this->flowJobStatus = 'EM_GERACAO';
            $this->upsertCurrentFlowJob();
            $this->persistFlowWorkstation();
        } catch (Throwable $exception) {
            report($exception);
            $this->nativeProductionStatus = 'ERRO';
            $this->nativeProductionError = $exception->getMessage();
            $this->persistFlowWorkstation();
        }
    }

    private function startNativeImageProduction(): void
    {
        try {
            $agent = AiAgent::query()->where('slug', 'marketing-ia')->first();
            $provider = AiProvider::query()
                ->where('status', 'ativo')
                ->whereIn('slug', ['google', 'gemini', 'google-gemini'])
                ->first();

            if (! $agent || ! $provider) {
                throw new \RuntimeException('Marketing IA não está preparado para iniciar a geração de imagem via Centro IA.');
            }

            $prompt = 'Crie um criativo publicitário quadrado 1:1 profissional para a campanha '.trim($this->flowCampaign).'. '
                .'Público: '.trim($this->flowAudience).'. '
                .'Objetivo: '.trim($this->flowObjective).'. '
                .'Mensagem principal: '.trim($this->flowMessage).'. '
                .'Direção visual: '.trim($this->flowStyle).'. '
                .'Não gere logotipo, watermark, marcas de terceiros ou texto duplicado. '
                .'Preserve composição limpa e área segura para aplicação determinística da marca oficial pelo Marketing IA.';

            $generation = app(AiMediaGenerationService::class)->generate(
                $agent,
                $provider,
                'image_generation',
                $prompt,
                (string) config('marketing_agents.native_studio.image_model', 'gemini-3.1-flash-image'),
            );

            if ((string) $generation->status !== 'Concluído' || ! $generation->asset_path) {
                throw new \RuntimeException((string) ($generation->error_message ?: $generation->output ?: 'A geração de imagem não foi concluída.'));
            }

            $this->nativeProductionJobRef = 'IMAGE-'.$generation->id;
            $this->nativeProductionFinalPath = (string) $generation->asset_path;
            $this->nativeProductionAssetUrl = (string) ($generation->asset_url ?? '');
            $this->nativeProductionPreviewUrl = URL::temporarySignedRoute(
                'marketing.native-image-preview',
                now()->addHours(2),
                ['generation' => $generation->id],
                false,
            );
            $this->nativeProductionStatus = 'EM_QA';
            $this->flowJobStatus = 'EM_QA';
            $this->upsertCurrentFlowJob();
            $this->persistFlowWorkstation();
        } catch (Throwable $exception) {
            report($exception);
            $this->nativeProductionStatus = 'ERRO';
            $this->flowJobStatus = 'ERRO';
            $this->nativeProductionError = $exception->getMessage();
            $this->upsertCurrentFlowJob();
            $this->persistFlowWorkstation();
        }
    }

    public function refreshNativeProduction(): void
    {
        $this->nativeProductionError = null;

        if ($this->nativeProductionJobRef === '') {
            $this->nativeProductionError = 'Nenhuma geração nativa em andamento.';
            return;
        }

        try {
            $job = app(GeminiVeoSceneRenderer::class)->refresh($this->nativeProductionJobRef);
            $status = (string) ($job['status'] ?? 'processing');

            if ($status === 'completed') {
                $this->nativeProductionAssetUrl = (string) ($job['render_ref'] ?? '');
                $this->nativeProductionStatus = 'GERADO';
                $this->flowJobStatus = 'GERADO';
                $this->upsertCurrentFlowJob();
            } elseif ($status === 'failed') {
                $this->nativeProductionStatus = 'ERRO';
                $this->nativeProductionError = 'O motor Veo informou falha na geração.';
            } else {
                $this->nativeProductionStatus = 'EM_GERACAO';
            }

            $this->persistFlowWorkstation();
        } catch (Throwable $exception) {
            report($exception);
            $this->nativeProductionError = $exception->getMessage();
            $this->persistFlowWorkstation();
        }
    }

    public function finalizeNativeProduction(): void
    {
        $this->nativeProductionError = null;

        if ($this->nativeProductionAssetUrl === '' || $this->flowJobId === '') {
            $this->nativeProductionError = 'A mídia-base ainda não está disponível para finalização.';
            return;
        }

        try {
            $versionId = 'NATIVE-'.strtoupper(substr(sha1($this->nativeProductionJobRef), 0, 10));
            $logoPath = (string) config('marketing_video.finalization.official_logo_path', base_path('assets/img/logo-vitrine-ai-pro.png'));

            $this->nativeProductionStatus = 'FINALIZANDO';
            $this->flowJobStatus = 'FINALIZANDO';
            $this->upsertCurrentFlowJob();
            $this->persistFlowWorkstation();

            $finalized = app(VideoFinalizationService::class)->finalizeFromUrl(
                $this->flowJobId,
                $versionId,
                $this->nativeProductionAssetUrl,
                $logoPath,
            );

            $this->nativeProductionFinalPath = (string) ($finalized['final_path'] ?? '');
            $this->nativeProductionPreviewUrl = URL::temporarySignedRoute(
                'marketing.native-video-preview',
                now()->addHours(2),
                ['job' => $this->flowJobId, 'version' => $versionId],
                false,
            );
            $this->nativeProductionStatus = 'EM_QA';
            $this->flowJobStatus = 'EM_QA';
            $this->upsertCurrentFlowJob();
            $this->persistFlowWorkstation();
        } catch (Throwable $exception) {
            report($exception);
            $this->nativeProductionStatus = 'GERADO';
            $this->flowJobStatus = 'GERADO';
            $this->nativeProductionError = 'A mídia foi gerada, mas a finalização técnica falhou: '.$exception->getMessage();
            $this->upsertCurrentFlowJob();
            $this->persistFlowWorkstation();
        }
    }

    private function nativeDurationSeconds(): int
    {
        $duration = (int) preg_replace('/[^0-9]/', '', $this->flowDuration);

        return in_array($duration, [4, 6, 8], true) ? $duration : 8;
    }

    public function clearFlowPackage(): void
    {
        $this->flowObjective = '';
        $this->flowMessage = '';
        $this->flowCta = '';
        $this->flowPackage = '';
        $this->flowJobId = '';
        $this->flowJobStatus = 'RASCUNHO';
        $this->flowGenerationSource = '';
        $this->flowError = null;
        $this->nativeProductionStatus = 'RASCUNHO';
        $this->nativeProductionJobRef = '';
        $this->nativeProductionAssetUrl = '';
        $this->nativeProductionFinalPath = '';
        $this->nativeProductionPreviewUrl = '';
        $this->nativeProductionError = null;
        $this->persistFlowWorkstation();
    }

    public function saveFlowBridgeConfiguration(): void
    {
        $this->flowError = null;
        $flowBridge = (array) config('marketing_agents.flow_bridge', []);
        $this->flowToolName = trim((string) ($flowBridge['official_tool_name'] ?? 'Vitrine Content Studio')) ?: 'Vitrine Content Studio';
        $this->flowProjectName = trim((string) ($flowBridge['official_project_name'] ?? 'Vitrine Social Mídia')) ?: 'Vitrine Social Mídia';
        $this->flowToolUrl = trim($this->flowToolUrl);

        if (! $this->hasValidFlowToolUrl()) {
            $this->flowError = 'Informe uma URL oficial do Google Flow em flow.google.com ou labs.google.';
            return;
        }

        if ($this->flowJobId !== '' && $this->flowPackage !== '' && $this->flowJobStatus === 'PREPARADO') {
            $this->flowJobStatus = 'PRONTO_PARA_FLOW';
            $this->upsertCurrentFlowJob();
        }

        $this->persistFlowWorkstation();
    }

    public function setFlowJobStatus(string $status): void
    {
        if ($status === 'APROVADO') {
            $job = collect($this->flowJobs)->first(fn (array $job): bool => ($job['id'] ?? '') === $this->flowJobId);
            if (is_array($job)) {
                $this->approveProductionJob($this->flowJobId, $this->pieceVersion($job));
            }
            return;
        }
        $this->flowError = 'Use as ações da peça. Geração, agendamento e publicação exigem confirmação da operação correspondente.';
    }

    public function hasValidFlowToolUrl(): bool
    {
        $url = trim($this->flowToolUrl);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $scheme === 'https' && in_array($host, ['flow.google.com', 'labs.google'], true);
    }

    public function getFlowHandoffPayload(): string
    {
        if ($this->flowJobId === '' || $this->flowPackage === '') {
            return '';
        }

        $formats = [
            'reel_9_16' => 'Reel vertical 9:16',
            'story_9_16' => 'Story vertical 9:16',
            'video_16_9' => 'Vídeo horizontal 16:9',
            'ad_1_1' => 'Criativo quadrado 1:1',
        ];
        $flowBridge = (array) config('marketing_agents.flow_bridge', []);
        $version = trim((string) ($flowBridge['handoff_version'] ?? '1.6')) ?: '1.6';
        $logoAsset = trim((string) ($flowBridge['official_logo_asset'] ?? 'LOGO_OFICIAL_VITRINE_IA_PRO')) ?: 'LOGO_OFICIAL_VITRINE_IA_PRO';
        $format = $formats[$this->flowFormat] ?? $this->flowFormat;
        $cta = trim($this->flowCta) !== '' ? trim($this->flowCta) : '[SEM CTA INFORMADO]';

        return "VITRINE_FLOW_HANDOFF_V{$version}\n"
            ."FONTE_DA_VERDADE: MARKETING_IA\n"
            ."JOB_ID: {$this->flowJobId}\n"
            ."FERRAMENTA_OFICIAL: {$this->flowToolName}\n"
            ."PROJETO_FLOW: {$this->flowProjectName}\n"
            ."CAMPANHA: {$this->flowCampaign}\n"
            ."OBJETIVO: {$this->flowObjective}\n"
            ."PUBLICO: {$this->flowAudience}\n"
            ."FORMATO: {$format}\n"
            ."DURACAO: {$this->flowDuration}\n"
            ."MENSAGEM_PRINCIPAL: {$this->flowMessage}\n"
            ."CTA_EXATO: {$cta}\n"
            ."ESTILO: {$this->flowStyle}\n"
            ."LOGO_ASSET: {$logoAsset}\n\n"
            ."REGRAS_DE_IMPORTACAO:\n"
            ."- Ignore e substitua qualquer valor padrão, histórico ou preenchido pelo Remix/Ferramenta do Flow.\n"
            ."- Use este handoff do Marketing IA como única fonte de campanha, objetivo, público, formato, duração, mensagem e CTA.\n"
            ."- Não invente outro CTA, slogan, marca, logo ou texto.\n"
            ."- Use somente o asset {$logoAsset} quando o logo fizer parte da cena; para assinatura/watermark, preserve a pós-produção do Marketing IA.\n"
            ."- Não gere enquanto os campos visíveis do Flow divergirem deste handoff.\n\n"
            ."PACOTE_CRIATIVO:\n{$this->flowPackage}";
    }

    public function getAntigravityFlowInstruction(): string
    {
        if ($this->flowJobId === '' || $this->flowPackage === '') {
            return '';
        }

        $url = $this->hasValidFlowToolUrl() ? $this->flowToolUrl : '[URL DO FLOW AINDA NÃO CONFIGURADA]';

        return "VITRINE FLOW OPERATOR\n"
            ."Job: {$this->flowJobId}\n"
            ."Ferramenta oficial: {$this->flowToolName}\n"
            ."Projeto Flow: {$this->flowProjectName}\n"
            ."URL: {$url}\n"
            ."Status esperado ao iniciar: PRONTO_PARA_FLOW\n\n"
            ."Instruções:\n"
            ."1. Abra a URL cadastrada no Chrome autenticado da conta Google autorizada.\n"
            ."2. Selecione o projeto Flow informado e a ferramenta oficial. Se o Google abrir uma cópia/Remix, não use os valores herdados.\n"
            ."3. Aplique o HANDOFF V1.6 abaixo e substitua todos os campos padrão ou históricos antes da geração.\n"
            ."4. Confirme visualmente que campanha, objetivo, público, formato, duração, mensagem e CTA coincidem com o handoff.\n"
            ."5. Revise texto, assets e identidade antes de consumir créditos.\n"
            ."6. Não aceite logo recriado por IA, texto duplicado nem marcas de terceiros não fornecidas.\n"
            ."7. Gere a mídia somente depois da conferência. Não publique, não regenere e não altere campanha sem autorização.\n"
            ."8. Ao concluir, registre evidência e retorne o job como GERADO.\n\n"
            ."HANDOFF V1.6:\n{$this->getFlowHandoffPayload()}";
    }

    private function generateFlowPackageWithGemini(string $system, string $userPrompt): string
    {
        $apiKey = trim((string) config('marketing_video.gemini_veo.api_key'));
        $baseUrl = rtrim((string) config('marketing_video.gemini_veo.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $model = trim((string) config('marketing_agents.native_studio.director_model', 'gemini-3.5-flash'));

        if ($apiKey === '') {
            throw new \RuntimeException('Gemini local não está configurado para o Flow Bridge.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withHeaders(['X-goog-api-key' => $apiKey])
            ->timeout(60)
            ->retry(2, 250, throw: false)
            ->post("{$baseUrl}/models/{$model}:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => "INSTRUÇÕES DO SISTEMA:\n{$system}\n\nSOLICITAÇÃO:\n{$userPrompt}"],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            $errorStatus = preg_replace('/[^A-Z0-9_\-]/i', '', (string) data_get($response->json(), 'error.status', '')) ?: 'UNKNOWN';
            throw new \RuntimeException('O fallback Gemini falhou: HTTP '.$response->status().' '.$errorStatus.'.');
        }

        $text = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));
        if ($text === '') {
            throw new \RuntimeException('O fallback Gemini retornou um pacote vazio.');
        }

        return $text;
    }

    private function createFlowJob(string $status): void
    {
        $this->flowJobId = 'MKT-PROD-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(2)));
        $this->flowJobStatus = $status === 'PREPARADO' ? 'PRONTO_PARA_PRODUCAO' : $status;
        $this->nativeProductionStatus = 'PRONTO_PARA_PRODUCAO';
        $this->nativeProductionJobRef = '';
        $this->nativeProductionAssetUrl = '';
        $this->nativeProductionFinalPath = '';
        $this->nativeProductionPreviewUrl = '';
        $this->upsertCurrentFlowJob();
    }

    private function upsertCurrentFlowJob(): void
    {
        if ($this->flowJobId === '') {
            return;
        }

        $job = [
            'id' => $this->flowJobId,
            'status' => $this->flowJobStatus,
            'campaign' => $this->flowCampaign,
            'format' => $this->flowFormat,
            'type' => $this->flowFormat === 'ad_1_1' ? 'image' : 'video',
            'tool_name' => $this->flowToolName,
            'project_name' => $this->flowProjectName,
            'generation_source' => $this->flowGenerationSource,
            'marketing_context' => $this->marketingContextKey,
            'asset_url' => $this->nativeProductionAssetUrl,
            'preview_url' => $this->nativeProductionPreviewUrl,
            'provider_job_ref' => $this->nativeProductionJobRef,
            'error' => $this->nativeProductionError,
            'updated_at' => now()->toISOString(),
        ];

        $jobs = collect($this->flowJobs)
            ->reject(fn (array $item): bool => (string) ($item['id'] ?? '') === $this->flowJobId)
            ->prepend($job)
            ->take(12)
            ->values()
            ->all();

        $this->flowJobs = $jobs;
    }

    public function refreshProductionBoard(): void
    {
        if ($this->flowJobs === []) {
            return;
        }

        $changed = false;

        $startedPreparedJob = false;

        foreach ($this->flowJobs as $index => $job) {
            if (($job['legacy'] ?? false) === true) {
                continue;
            }

            $jobStatus = (string) ($job['status'] ?? '');

            if (
                in_array($jobStatus, ['ERRO', 'BLOQUEADO_CREDITO'], true)
                && $this->isMediaQuotaBlocked((string) ($job['error'] ?? ''))
                && empty($job['fallback_retry_attempted'])
            ) {
                $this->flowJobs[$index]['status'] = 'PRONTO_PARA_PRODUCAO';
                $this->flowJobs[$index]['error'] = null;
                $this->flowJobs[$index]['fallback_retry_attempted'] = true;
                $this->flowJobs[$index]['updated_at'] = now()->toISOString();
                $job = $this->flowJobs[$index];
                $jobStatus = 'PRONTO_PARA_PRODUCAO';
                $changed = true;
            }

            if ($jobStatus === 'PRONTO_PARA_PRODUCAO' && ! $startedPreparedJob) {
                $startedPreparedJob = true;

                try {
                    $directorJob = (array) ($job['director_job'] ?? []);
                    $brand = trim((string) ($job['project_name'] ?? $this->flowProjectName)) ?: $this->flowProjectName;
                    $produced = $this->dispatchDirectorJob(
                        $directorJob,
                        $brand,
                        $index + 1,
                        (string) ($job['id'] ?? '')
                    );
                    $produced = array_replace($job, $produced);
                    $produced['director_job'] = $directorJob;
                    if (isset($produced['_gallery_etag'])) {
                        $this->saveGalleryPiece($produced);
                    }
                    $this->flowJobs[$index] = $produced;
                    $changed = true;

                    if ((string) ($job['id'] ?? '') === $this->flowJobId) {
                        $this->flowJobStatus = (string) ($produced['status'] ?? 'EM_GERACAO');
                        $this->nativeProductionStatus = $this->flowJobStatus;
                        $this->flowFormat = (string) ($produced['format'] ?? $this->flowFormat);
                        $this->nativeProductionJobRef = (string) ($produced['provider_job_ref'] ?? '');
                        $this->nativeProductionAssetUrl = (string) ($produced['asset_url'] ?? '');
                        $this->nativeProductionPreviewUrl = (string) ($produced['preview_url'] ?? '');
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $quotaBlocked = $this->isMediaQuotaBlocked($exception->getMessage());
                    $this->flowJobs[$index]['status'] = $quotaBlocked ? 'BLOQUEADO_CREDITO' : 'ERRO';
                    $this->flowJobs[$index]['error'] = $quotaBlocked
                        ? 'Crédito de geração indisponível no provedor atual. A peça foi preservada e pode ser tentada novamente.'
                        : $exception->getMessage();
                    $this->flowJobs[$index]['updated_at'] = now()->toISOString();
                    $changed = true;

                    if ((string) ($job['id'] ?? '') === $this->flowJobId) {
                        $this->flowJobStatus = (string) $this->flowJobs[$index]['status'];
                        $this->nativeProductionStatus = $this->flowJobStatus;
                        $this->nativeProductionError = (string) $this->flowJobs[$index]['error'];
                    }
                }

                continue;
            }

            if ($jobStatus !== 'EM_GERACAO') {
                continue;
            }

            $jobRef = trim((string) ($job['provider_job_ref'] ?? ''));
            if ($jobRef === '') {
                continue;
            }

            try {
                $result = app(GeminiVeoSceneRenderer::class)->refresh($jobRef);
                $status = (string) ($result['status'] ?? 'processing');

                if ($status === 'completed') {
                    $this->flowJobs[$index]['status'] = 'GERADO';
                    $this->flowJobs[$index]['asset_url'] = (string) ($result['render_ref'] ?? '');
                    $this->flowJobs[$index]['error'] = null;
                    $this->flowJobs[$index]['updated_at'] = now()->toISOString();
                    $changed = true;

                    if ((string) ($job['id'] ?? '') === $this->flowJobId) {
                        $this->flowJobStatus = 'GERADO';
                        $this->nativeProductionStatus = 'GERADO';
                        $this->nativeProductionAssetUrl = (string) ($result['render_ref'] ?? '');
                    }
                } elseif ($status === 'failed') {
                    $this->flowJobs[$index]['status'] = 'ERRO';
                    $this->flowJobs[$index]['error'] = 'O motor de vídeo selecionado pelo orquestrador informou falha na geração.';
                    $this->flowJobs[$index]['updated_at'] = now()->toISOString();
                    $changed = true;

                    if ((string) ($job['id'] ?? '') === $this->flowJobId) {
                        $this->flowJobStatus = 'ERRO';
                        $this->nativeProductionStatus = 'ERRO';
                        $this->nativeProductionError = 'O motor Veo informou falha na geração.';
                    }
                }
            } catch (Throwable $exception) {
                report($exception);
                $this->flowJobs[$index]['error'] = $exception->getMessage();
                $this->flowJobs[$index]['updated_at'] = now()->toISOString();
                $changed = true;
            }
        }

        if ($changed) {
            $this->persistFlowWorkstation();
        }
    }

    public function retryProductionJob(string $jobId): void
    {
        foreach ($this->flowJobs as $index => $job) {
            if ((string) ($job['id'] ?? '') !== $jobId) {
                continue;
            }

            if (! in_array((string) ($job['status'] ?? ''), ['ERRO', 'BLOQUEADO_CREDITO'], true)) {
                return;
            }

            $this->flowJobs[$index]['status'] = 'PRONTO_PARA_PRODUCAO';
            $this->flowJobs[$index]['error'] = null;
            $this->flowJobs[$index]['updated_at'] = now()->toISOString();

            if ($jobId === $this->flowJobId) {
                $this->flowJobStatus = 'PRONTO_PARA_PRODUCAO';
                $this->nativeProductionStatus = 'PRONTO_PARA_PRODUCAO';
                $this->nativeProductionError = null;
            }

            $this->persistFlowWorkstation();
            return;
        }
    }

    private function isMediaQuotaBlocked(string $message): bool
    {
        $normalized = strtoupper($message);

        return str_contains($normalized, 'RESOURCE_EXHAUSTED')
            || str_contains($normalized, 'HTTP 402')
            || str_contains($normalized, ':402')
            || str_contains($normalized, 'QUOTA_EXHAUSTED')
            || str_contains($normalized, 'GEMINI_VEO_QUOTA_EXHAUSTED')
            || str_contains($normalized, 'PREPAYMENT CREDITS ARE DEPLETED');
    }

    private function persistFlowWorkstation(): void
    {
        if (auth()->check()) {
            foreach ($this->flowJobs as &$job) {
                if (! empty($job['id']) && empty($job['legacy'])) {
                    $this->saveGalleryPiece($job);
                }
            }
            unset($job);
        }
        session([
            'marketing_workstation.production.'.$this->marketingContextKey => [
                'campaign' => $this->flowCampaign,
                'objective' => $this->flowObjective,
                'audience' => $this->flowAudience,
                'format' => $this->flowFormat,
                'duration' => $this->flowDuration,
                'message' => $this->flowMessage,
                'cta' => $this->flowCta,
                'style' => $this->flowStyle,
                'package' => $this->flowPackage,
                'tool_name' => $this->flowToolName,
                'tool_url' => $this->flowToolUrl,
                'project_name' => $this->flowProjectName,
                'job_id' => $this->flowJobId,
                'job_status' => $this->flowJobStatus,
                'generation_source' => $this->flowGenerationSource,
                'jobs' => $this->flowJobs,
                'native_status' => $this->nativeProductionStatus,
                'native_job_ref' => $this->nativeProductionJobRef,
                'native_asset_url' => $this->nativeProductionAssetUrl,
                'native_final_path' => $this->nativeProductionFinalPath,
                'native_preview_url' => $this->nativeProductionPreviewUrl,
                'marketing_context' => $this->marketingContextKey,
            ],
            'marketing_context.key' => $this->marketingContextKey,
        ]);
    }

    private function normalizeProductionJobs(array $jobs): array
    {
        return array_values(array_map(static function (array $job): array {
            $id = (string) ($job['id'] ?? '');
            if (str_starts_with($id, 'FLOW-')) {
                $job['legacy'] = true;
                $job['status'] = 'LEGADO_FLOW';
            } else {
                $job['legacy'] = false;
            }

            return $job;
        }, $jobs));
    }

    public function newCopilotSession(): void
    {
        $this->copilotSessionId = 'MKT-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(3)));
        $this->copilotMessages = [];
        $this->copilotMessage = '';
        $this->copilotError = null;
        $this->persistCopilot();
    }

    private function syncProductionBriefFromText(string $text): void
    {
        $patterns = [
            'flowCampaign' => '/(?:^|\n)\s*CAMPANHA\s*:\s*(.+)$/imu',
            'flowObjective' => '/(?:^|\n)\s*OBJETIVO\s*:\s*(.+)$/imu',
            'flowAudience' => '/(?:^|\n)\s*P[ÚU]BLICO\s*:\s*(.+)$/imu',
            'flowFormat' => '/(?:^|\n)\s*FORMATO\s*:\s*(.+)$/imu',
            'flowDuration' => '/(?:^|\n)\s*DURA[CÇ][ÃA]O\s*:\s*(.+)$/imu',
            'flowMessage' => '/(?:^|\n)\s*MENSAGEM(?:\s+PRINCIPAL)?\s*:\s*(.+)$/imu',
            'flowCta' => '/(?:^|\n)\s*CTA\s*:\s*(.+)$/imu',
            'flowStyle' => '/(?:^|\n)\s*ESTILO(?:\s+VISUAL)?\s*:\s*(.+)$/imu',
        ];

        $matched = false;
        foreach ($patterns as $property => $pattern) {
            if (! preg_match($pattern, $text, $matches)) {
                continue;
            }
            $value = trim((string) ($matches[1] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($property === 'flowFormat') {
                $normalized = mb_strtolower($value);
                $value = str_contains($normalized, '16:9') ? 'video_16_9'
                    : (str_contains($normalized, '1:1') ? 'ad_1_1'
                    : (str_contains($normalized, 'story') ? 'story_9_16' : 'reel_9_16'));
            }

            $this->{$property} = $value;
            $matched = true;
        }

        if ($matched) {
            $this->persistFlowWorkstation();
        }
    }

    public function recoverPendingRevision(): void
    {
        if ($this->flowJobs === [] || $this->copilotMessages === []) {
            return;
        }

        $pendingMessage = null;
        for ($index = count($this->copilotMessages) - 1; $index >= 0; $index--) {
            $item = (array) $this->copilotMessages[$index];
            if (($item['role'] ?? '') !== 'user') {
                continue;
            }

            $content = trim((string) ($item['content'] ?? ''));
            if ($content !== '' && $this->isRevisionRequest($content)) {
                $pendingMessage = $content;
                break;
            }
        }

        if ($pendingMessage === null) {
            return;
        }

        foreach ($this->flowJobs as $job) {
            if (trim((string) ($job['revision_reason'] ?? '')) === $pendingMessage) {
                return;
            }
        }

        $this->reviseProductionJobFromDirector($pendingMessage, '');
        $this->persistCopilot();
    }

    private function isRevisionRequest(string $message): bool
    {
        $normalized = mb_strtolower($message);

        foreach (['corrigir', 'corrija', 'correção', 'correcao', 'ajustar', 'ajuste', 'refazer', 'refaça', 'revisar', 'revisão', 'revisao', 'alterar', 'alteração', 'alteracao', 'não aprovado', 'nao aprovado'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function reviseProductionJobFromDirector(string $message, string $directorReply, ?string $selectedJobId = null): void
    {
        if ($this->flowJobs === []) {
            $this->copilotMessages[] = [
                'role' => 'assistant',
                'content' => 'A correção foi entendida, mas não há uma peça produzida nesta sessão para vincular à revisão.',
                'at' => now()->toISOString(),
                'model' => 'marketing-ia-orchestrator',
                'execution_id' => null,
            ];
            return;
        }

        $targetIndex = $selectedJobId === null
            ? $this->resolveRevisionTargetIndex($message)
            : collect($this->flowJobs)->search(fn (array $job): bool => ($job['id'] ?? '') === $selectedJobId);
        if ($targetIndex === false) {
            $targetIndex = null;
        }
        if ($targetIndex === null || ! isset($this->flowJobs[$targetIndex])) {
            $this->copilotMessages[] = [
                'role' => 'assistant',
                'content' => 'A correção foi registrada, mas não consegui identificar com segurança qual peça deve ser revisada.',
                'at' => now()->toISOString(),
                'model' => 'marketing-ia-orchestrator',
                'execution_id' => null,
            ];
            return;
        }

        $current = (array) $this->flowJobs[$targetIndex];
        $jobId = (string) ($current['id'] ?? '');
        $brand = trim((string) ($current['project_name'] ?? ($this->getMarketingContext()['brand'] ?? 'Marca do cliente')));
        $directorJob = (array) ($current['director_job'] ?? []);
        $originalIdea = trim((string) ($directorJob['idea'] ?? $current['title'] ?? $this->flowMessage));
        $directorJob['idea'] = $originalIdea.". CORREÇÃO SOLICITADA PELO USUÁRIO: ".$message.'. Preserve o objetivo, a identidade e o formato da peça anterior; altere somente o necessário para atender a correção.';
        $directorJob['title'] = (string) ($directorJob['title'] ?? $current['title'] ?? 'Peça revisada');
        $directorJob['type'] = (string) ($directorJob['type'] ?? $current['type'] ?? 'image');
        $directorJob['format'] = (string) ($directorJob['format'] ?? $current['format'] ?? 'ad_1_1');

        if (! in_array($current['status'] ?? '', ['EM_QA', 'GERADO', 'APROVADO', 'PLANEJADO_EDITORIAL', 'REPROVADO_QA', 'ERRO'], true)) {
            $this->pieceError = 'Aguarde a produção da peça antes de revisar.';
            return;
        }
        $history = (array) ($current['revision_history'] ?? []);
        $snapshot = $current;
        unset($snapshot['revision_history']);
        $history[] = $snapshot;
        $current['revision_history'] = $history;
        $current['approved_version'] = null;
        $current['approved_at'] = null;
        $current['approved_by'] = null;
        $current['scheduled_at'] = null;
        $current['publication_status'] = 'REAPPROVAL_REQUIRED';
        $current['status'] = 'CORRECAO_SOLICITADA';
        $current['revision_reason'] = $message;
        $current['revision_count'] = ((int) ($current['revision_count'] ?? 0)) + 1;
        $current['previous_asset_url'] = (string) ($current['asset_url'] ?? '');
        $current['previous_preview_url'] = (string) ($current['preview_url'] ?? '');
        $current['supersedes_provider_job_ref'] = (string) ($current['provider_job_ref'] ?? '');
        $current['updated_at'] = now()->toISOString();
        $this->saveGalleryPiece($current);
        $this->flowJobs[$targetIndex] = $current;
        $this->persistFlowWorkstation();

        try {
            $revised = $this->dispatchDirectorJob($directorJob, $brand, $targetIndex + 1, $jobId);
            $revised['_gallery_etag'] = $current['_gallery_etag'];
            $revised['revision_history'] = $history;
            $revised['approved_version'] = null;
            $revised['scheduled_at'] = null;
            $revised['publication_status'] = 'REAPPROVAL_REQUIRED';
            $revised['revision_reason'] = $message;
            $revised['revision_count'] = (int) $current['revision_count'];
            $revised['previous_asset_url'] = (string) $current['previous_asset_url'];
            $revised['previous_preview_url'] = (string) $current['previous_preview_url'];
            $revised['supersedes_provider_job_ref'] = (string) $current['supersedes_provider_job_ref'];
            $revised['director_job'] = $directorJob;
            $revised['updated_at'] = now()->toISOString();
            $this->saveGalleryPiece($revised);
            $this->flowJobs[$targetIndex] = $revised;

            $this->flowJobId = (string) ($revised['id'] ?? $jobId);
            $this->flowJobStatus = (string) ($revised['status'] ?? 'PRONTO_PARA_PRODUCAO');
            $this->flowFormat = (string) ($revised['format'] ?? $this->flowFormat);
            $this->nativeProductionStatus = $this->flowJobStatus;
            $this->nativeProductionJobRef = (string) ($revised['provider_job_ref'] ?? '');
            $this->nativeProductionAssetUrl = (string) ($revised['asset_url'] ?? '');
            $this->nativeProductionPreviewUrl = (string) ($revised['preview_url'] ?? '');
            $this->persistFlowWorkstation();

            $this->copilotMessages[] = [
                'role' => 'assistant',
                'content' => 'Correção enviada para produção na mesma peça '.$this->flowJobId.'. Versão de revisão #'.((int) $revised['revision_count']).' criada e encaminhada novamente para QA.',
                'at' => now()->toISOString(),
                'model' => 'marketing-ia-orchestrator',
                'execution_id' => null,
            ];
        } catch (Throwable $exception) {
            report($exception);
            $current['status'] = 'ERRO';
            $current['error'] = 'Falha ao gerar a correção: '.$exception->getMessage();
            $current['updated_at'] = now()->toISOString();
            $this->saveGalleryPiece($current);
            $this->flowJobs[$targetIndex] = $current;
            $this->persistFlowWorkstation();
            $this->copilotMessages[] = [
                'role' => 'assistant',
                'content' => 'A correção foi vinculada à peça '.$jobId.', mas a nova versão não foi concluída: '.$exception->getMessage(),
                'at' => now()->toISOString(),
                'model' => 'marketing-ia-orchestrator',
                'execution_id' => null,
            ];
        }
    }

    private function resolveRevisionTargetIndex(string $message): ?int
    {
        $normalized = mb_strtolower($message);
        $ids = [];
        $titles = [];
        foreach ($this->flowJobs as $index => $job) {
            $id = mb_strtolower((string) ($job['id'] ?? ''));
            $title = mb_strtolower((string) ($job['title'] ?? ''));
            if ($id !== '' && str_contains($normalized, $id)) {
                $ids[] = $index;
            }
            if ($title !== '' && mb_strlen($title) >= 4 && str_contains($normalized, $title)) {
                $titles[] = $index;
            }
        }
        if (count($ids) === 1) {
            return $ids[0];
        }
        if (count($ids) > 1) {
            return null;
        }
        return count($titles) === 1 ? $titles[0] : null;
    }

    private function shouldAutoProduceFromDirector(string $message): bool
    {
        $normalized = mb_strtolower($message);

        return str_contains($normalized, 'campanha')
            || str_contains($normalized, 'crie')
            || str_contains($normalized, 'criar')
            || str_contains($normalized, 'gere')
            || str_contains($normalized, 'gerar')
            || str_contains($normalized, 'produza');
    }

    private function generateDirectorCampaignPlan(string $system, string $userPrompt): string
    {
        $hub = (array) config('marketing_agents.hub', []);
        $url = trim((string) ($hub['url'] ?? ''));
        $token = trim((string) ($hub['token'] ?? ''));
        $projectId = trim((string) ($hub['project_id'] ?? 'vitrine-marketing-agents-core'));
        $capability = trim((string) ($hub['capability'] ?? 'marketing_generation'));

        if ($url !== '' && $token !== '') {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($token)
                ->withHeaders(['X-Vitrine-Project' => $projectId])
                ->timeout(max(1, min((int) ($hub['timeout'] ?? 60), 120)))
                ->retry(2, 250, throw: false)
                ->post($url, [
                    'project_id' => $projectId,
                    'capability' => $capability,
                    'input' => [
                        'system' => $system,
                        'user' => $userPrompt,
                        'response_format' => 'json',
                        'temperature' => 0.2,
                    ],
                ]);

            if ($response->successful() && $response->json('ok')) {
                $output = trim((string) $response->json('output_text'));
                if ($output !== '') {
                    return $output;
                }
            }

            logger()->warning('Diretor Marketing IA: planejamento estruturado via Centro IA falhou; usando fallback direto.', [
                'http_status' => $response->status(),
                'error' => (string) ($response->json('error') ?? 'unknown'),
                'capability' => $capability,
            ]);
        }

        return $this->generateFlowPackageWithGemini($system, $userPrompt);
    }

    private function decodeDirectorPlan(string $raw): array
    {
        $candidate = trim($raw);
        $candidate = preg_replace('/^\x60\x60\x60(?:json)?\s*|\s*\x60\x60\x60$/iu', '', $candidate) ?? $candidate;

        $decoded = json_decode($candidate, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($candidate, '{');
        $end = strrpos($candidate, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($candidate, $start, $end - $start + 1), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('O Diretor retornou um plano de campanha inválido. Tente novamente; o pedido foi preservado no chat.');
    }

    private function autoProduceDirectorCampaign(string $message, string $directorReply): void
    {
        try {
            $context = $this->getMarketingContext();
            $brand = trim((string) ($context['brand'] ?? $this->flowProjectName ?? 'Marca do cliente'));

            $system = 'Retorne APENAS JSON valido, sem markdown, com esta estrutura: '
                .'{"campaign":{"name":"","objective":"","audience":"","message":"","cta":"","style":"",'
                .'"creative_concept":{"id":"","central_idea":"","visual_language":"","tone":"","consistency_rules":[""]}},'
                .'"jobs":[{"type":"image|video","format":"ad_1_1|story_9_16|reel_9_16|video_16_9","channel_role":"feed|story|reel|support",'
                .'"title":"","idea":"","caption":"","cta":"","duration_seconds":8}]}. '
                .'Crie um pacote coerente de 3 a 5 pecas derivadas do MESMO creative_concept. '
                .'Por padrao, inclua Feed 1:1, Story 9:16 e Reel 9:16; adicione outras pecas somente quando ajudarem o objetivo. '
                .'Cada job deve adaptar o mesmo conceito ao formato/canal, sem reinventar mensagem, identidade ou proposta. '
                .'Nao invente fatos, metricas, depoimentos ou precos. Nao escolha modelo ou provedor: Video e imagem usam o orquestrador dinamico do Centro IA.';

            $userPrompt = "MARCA: ".$brand."\nCONTEXTO: ".$this->marketingContextKey."\nPEDIDO: ".$message."\nPLANO DO DIRETOR: ".$directorReply;
            $raw = trim($this->generateDirectorCampaignPlan($system, $userPrompt));
            $plan = $this->decodeDirectorPlan($raw);

            $campaign = (array) ($plan['campaign'] ?? []);
            $creativeConcept = (array) ($campaign['creative_concept'] ?? []);
            $jobs = array_values(array_filter((array) ($plan['jobs'] ?? []), 'is_array'));
            $jobs = $this->normalizeCampaignPieceMix($jobs, $campaign, $brand);

            if ($jobs === []) {
                throw new \RuntimeException('O Diretor nao retornou pecas executaveis.');
            }

            $this->flowCampaign = trim((string) ($campaign['name'] ?? $brand)) ?: $brand;
            $this->flowObjective = trim((string) ($campaign['objective'] ?? 'Divulgacao'));
            $this->flowAudience = trim((string) ($campaign['audience'] ?? $this->flowAudience));
            $this->flowMessage = trim((string) ($campaign['message'] ?? $message));
            $this->flowCta = trim((string) ($campaign['cta'] ?? ''));
            $this->flowStyle = trim((string) ($campaign['style'] ?? $this->flowStyle));
            $this->flowGenerationSource = 'Diretor Auto Campaign Builder';

            $preparedJobs = [];
            foreach (array_slice($jobs, 0, 5) as $index => $job) {
                $type = strtolower(trim((string) ($job['type'] ?? 'image')));
                $format = trim((string) ($job['format'] ?? ($type === 'video' ? 'reel_9_16' : 'ad_1_1')));
                if (! in_array($format, ['ad_1_1', 'story_9_16', 'reel_9_16', 'video_16_9'], true)) {
                    $format = $type === 'video' ? 'reel_9_16' : 'ad_1_1';
                }

                $preparedJobs[] = [
                    'id' => 'MKT-AUTO-'.now()->format('Ymd-His').'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'-'.strtoupper(bin2hex(random_bytes(2))),
                    'campaign' => $this->flowCampaign,
                    'title' => trim((string) ($job['title'] ?? 'Peça '.($index + 1))),
                    'format' => $format,
                    'type' => $type,
                    'status' => 'PRONTO_PARA_PRODUCAO',
                    'project_name' => $brand,
                    'generation_source' => 'Diretor Auto Campaign Builder',
                    'marketing_context' => $this->marketingContextKey,
                    'updated_at' => now()->toISOString(),
                    'asset_url' => '',
                    'preview_url' => '',
                    'provider_job_ref' => '',
                    'error' => null,
                    'creative_concept' => $creativeConcept,
                    'concept_id' => trim((string) ($creativeConcept['id'] ?? '')) ?: 'CONCEPT-'.strtoupper(substr(sha1($this->flowCampaign.'|'.$this->flowMessage), 0, 10)),
                    'channel_role' => trim((string) ($job['channel_role'] ?? 'support')),
                    'director_job' => array_merge($job, ['creative_concept' => $creativeConcept]),
                ];
            }

            $this->flowJobs = array_values(array_slice(array_merge($preparedJobs, $this->flowJobs), 0, 12));
            $this->flowPackage = json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: $directorReply;

            if ($preparedJobs !== []) {
                $first = $preparedJobs[0];
                $this->flowJobId = (string) ($first['id'] ?? '');
                $this->flowJobStatus = 'PRONTO_PARA_PRODUCAO';
                $this->nativeProductionStatus = 'PRONTO_PARA_PRODUCAO';
                $this->flowFormat = (string) ($first['format'] ?? $this->flowFormat);
                $this->nativeProductionJobRef = '';
                $this->nativeProductionAssetUrl = '';
                $this->nativeProductionPreviewUrl = '';
            }

            $this->persistFlowWorkstation();

            $summary = 'Produção preparada: '.count($preparedJobs).' conteúdos já estão visíveis e entrarão em produção automaticamente.';
            $this->archiveCompletedCopilotRequest($message, $directorReply, $summary, $preparedJobs);
            $this->copilotMessages = [[
                'role' => 'assistant',
                'content' => $summary,
                'at' => now()->toISOString(),
                'model' => 'marketing-ia-orchestrator',
                'execution_id' => null,
                'kind' => 'production_summary',
            ]];
            $this->copilotSessionId = 'MKT-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(3)));
        } catch (Throwable $exception) {
            report($exception);
            $this->copilotMessages[] = [
                'role' => 'assistant',
                'content' => 'A estrategia foi criada, mas a automacao de producao falhou: '.$exception->getMessage(),
                'at' => now()->toISOString(),
                'model' => 'marketing-ia-orchestrator',
                'execution_id' => null,
            ];
        }
    }

    private function normalizeCampaignPieceMix(array $jobs, array $campaign, string $brand): array
    {
        $concept = (array) ($campaign['creative_concept'] ?? []);
        $centralIdea = trim((string) ($concept['central_idea'] ?? $campaign['message'] ?? $this->flowMessage));
        $cta = trim((string) ($campaign['cta'] ?? $this->flowCta));

        $required = [
            'ad_1_1' => ['type' => 'image', 'channel_role' => 'feed', 'title' => 'Feed principal'],
            'story_9_16' => ['type' => 'image', 'channel_role' => 'story', 'title' => 'Story de campanha'],
            'reel_9_16' => ['type' => 'video', 'channel_role' => 'reel', 'title' => 'Reel principal'],
        ];

        $normalized = [];
        foreach ($jobs as $job) {
            if (! is_array($job)) {
                continue;
            }

            $format = trim((string) ($job['format'] ?? ''));
            if (! in_array($format, ['ad_1_1', 'story_9_16', 'reel_9_16', 'video_16_9'], true)) {
                continue;
            }

            $job['type'] = strtolower(trim((string) ($job['type'] ?? ($format === 'ad_1_1' || $format === 'story_9_16' ? 'image' : 'video'))));
            $job['channel_role'] = trim((string) ($job['channel_role'] ?? match ($format) {
                'ad_1_1' => 'feed',
                'story_9_16' => 'story',
                'reel_9_16' => 'reel',
                default => 'support',
            }));
            $job['idea'] = trim((string) ($job['idea'] ?? $centralIdea));
            $job['cta'] = trim((string) ($job['cta'] ?? $cta));
            $job['creative_concept'] = $concept;
            $normalized[] = $job;
        }

        $formats = array_map(static fn (array $job): string => (string) ($job['format'] ?? ''), $normalized);
        foreach ($required as $format => $defaults) {
            if (in_array($format, $formats, true)) {
                continue;
            }

            $normalized[] = [
                ...$defaults,
                'format' => $format,
                'idea' => $centralIdea !== '' ? $centralIdea : 'Adaptar o conceito central da campanha para '.$brand,
                'caption' => '',
                'cta' => $cta,
                'duration_seconds' => $format === 'reel_9_16' ? 8 : null,
                'creative_concept' => $concept,
            ];
        }

        return array_slice($normalized, 0, 5);
    }

    private function dispatchDirectorJob(array $job, string $brand, int $sequence, ?string $existingId = null): array
    {
        $type = strtolower(trim((string) ($job['type'] ?? 'image')));
        $format = trim((string) ($job['format'] ?? ($type === 'video' ? 'reel_9_16' : 'ad_1_1')));
        if (! in_array($format, ['ad_1_1', 'story_9_16', 'reel_9_16', 'video_16_9'], true)) {
            $format = $type === 'video' ? 'reel_9_16' : 'ad_1_1';
        }

        $id = $existingId ?: 'MKT-AUTO-'.now()->format('Ymd-His').'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT).'-'.strtoupper(bin2hex(random_bytes(2)));
        $title = trim((string) ($job['title'] ?? 'Peca '.$sequence));
        $idea = trim((string) ($job['idea'] ?? $this->flowMessage));
        $caption = trim((string) ($job['caption'] ?? ''));
        $cta = trim((string) ($job['cta'] ?? $this->flowCta));
        $creativeConcept = (array) ($job['creative_concept'] ?? []);
        $conceptId = trim((string) ($creativeConcept['id'] ?? '')) ?: 'CONCEPT-'.strtoupper(substr(sha1($this->flowCampaign.'|'.$this->flowMessage), 0, 10));
        $channelRole = trim((string) ($job['channel_role'] ?? 'support'));

        $base = [
            'id' => $id,
            'campaign' => $this->flowCampaign,
            'title' => $title,
            'format' => $format,
            'type' => $type,
            'status' => 'PRONTO_PARA_PRODUCAO',
            'project_name' => $brand,
            'generation_source' => 'Diretor Auto Campaign Builder',
            'marketing_context' => $this->marketingContextKey,
            'updated_at' => now()->toISOString(),
            'asset_url' => '',
            'preview_url' => '',
            'provider_job_ref' => '',
            'creative_concept' => $creativeConcept,
            'concept_id' => $conceptId,
            'channel_role' => $channelRole,
        ];

        $conceptSummary = trim(implode(' | ', array_filter([
            (string) ($creativeConcept['central_idea'] ?? ''),
            (string) ($creativeConcept['visual_language'] ?? ''),
            (string) ($creativeConcept['tone'] ?? ''),
            implode('; ', array_map('strval', (array) ($creativeConcept['consistency_rules'] ?? []))),
        ])));

        $prompt = 'Marca: '.$brand.'. Conceito compartilhado da campanha: '.($conceptSummary !== '' ? $conceptSummary : $this->flowMessage).'. '
            .'Papel desta peca: '.$channelRole.'. Ideia adaptada ao formato: '.$idea.'. Objetivo: '.$this->flowObjective.'. Publico: '.$this->flowAudience.'. '
            .'Titulo de referencia: '.$title.'. Legenda de referencia: '.$caption.'. CTA: '.$cta.'. Estilo: '.$this->flowStyle.'. '
            .'Preserve o MESMO conceito, linguagem visual, tom e promessa das demais pecas; adapte apenas composicao, ritmo e enquadramento ao formato '.$format.'. '
            .'Nao invente fatos, nao use marcas de terceiros, nao recrie logotipo e nao renderize texto legivel na midia-base.';

        if ($type === 'video') {
            $aspectRatio = $format === 'video_16_9' ? '16:9' : '9:16';
            $duration = (int) ($job['duration_seconds'] ?? 8);
            $duration = in_array($duration, [4, 6, 8], true) ? $duration : 8;

            $project = new VideoProject(
                projectId: $id,
                productId: 'marketing-ia-engine',
                campaignId: ((string) str($this->flowCampaign)->slug()) ?: 'marketing-ia',
            );
            $scene = $project->addScene('SCENE-01', 1, ['prompt' => $prompt]);
            $result = app(GeminiVeoSceneRenderer::class)->dispatch($project, $scene, [
                'aspect_ratio' => $aspectRatio,
                'duration_seconds' => $duration,
                'resolution' => '720p',
            ]);

            $renderRef = trim((string) ($result['render_ref'] ?? ''));
            $resultStatus = strtolower((string) ($result['status'] ?? 'processing'));

            if ($resultStatus === 'completed' && $renderRef !== '') {
                $base['status'] = 'GERADO';
                $base['asset_url'] = $renderRef;
                $base['provider_job_ref'] = '';
                return $base;
            }

            $base['status'] = 'EM_GERACAO';
            $base['provider_job_ref'] = (string) ($result['job_ref'] ?? '');

            return $base;
        }

        $provider = AiProvider::query()
            ->whereIn('slug', ['google', 'gemini', 'google-gemini'])
            ->where('status', 'ativo')
            ->first();

        if (! $provider) {
            $provider = AiProvider::query()->create([
                'name' => 'Gemini',
                'slug' => 'gemini',
                'provider_type' => 'gemini',
                'status' => 'ativo',
                'notes' => 'Google Gemini para estratégia, conteúdo e geração de mídia.',
                'config' => [
                    'model_default' => 'gemini-2.5-flash',
                    'capabilities' => ['marketing_strategy', 'copy', 'critical_review', 'image_generation'],
                    'models' => [
                        'image_generation' => (string) config('marketing_agents.native_studio.image_model', 'gemini-3.1-flash-image'),
                    ],
                ],
            ]);
        }

        $agent = AiAgent::query()->where('slug', 'marketing-ia')->first();

        if (! $agent) {
            $agent = AiAgent::query()->create([
                'ai_provider_id' => $provider->id,
                'name' => 'Marketing IA',
                'slug' => 'marketing-ia',
                'type' => 'corporativo',
                'product_scope' => 'Marketing',
                'version' => '1.0',
                'model_name' => null,
                'status' => 'online',
                'is_internal' => true,
                'description' => 'Campanhas, criativos, vídeos e conteúdo para redes sociais.',
                'config' => [],
            ]);
        } elseif (! $agent->ai_provider_id) {
            $agent->update(['ai_provider_id' => $provider->id]);
        }

        $generation = app(AiMediaGenerationService::class)->generate(
            $agent,
            $provider,
            'image_generation',
            $prompt,
            (string) config('marketing_agents.native_studio.image_model', 'gemini-3.1-flash-image'),
            ['aspect_ratio' => $format === 'story_9_16' ? '9:16' : '1:1'],
        );

        if ((string) $generation->status !== 'Concluído' || ! $generation->asset_path) {
            throw new \RuntimeException((string) ($generation->error_message ?: 'Falha ao gerar imagem.'));
        }

        $base['status'] = 'EM_QA';
        $base['asset_url'] = (string) ($generation->asset_url ?? '');
        $base['preview_url'] = URL::temporarySignedRoute(
            'marketing.native-image-preview',
            now()->addHours(2),
            ['generation' => $generation->id],
            false,
        );
        $base['provider_job_ref'] = 'IMAGE-'.$generation->id;

        return $base;
    }

    private function archiveCompletedCopilotRequest(string $message, string $directorReply, string $summary, array $jobs): void
    {
        $archiveId = 'ARCH-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(2)));
        $archive = [
            'id' => $archiveId,
            'campaign' => $this->flowCampaign,
            'message' => $message,
            'director_reply' => $directorReply,
            'summary' => $summary,
            'jobs' => $jobs,
            'messages' => $this->copilotMessages,
            'created_at' => now()->toISOString(),
        ];

        $this->copilotArchives = collect($this->copilotArchives)
            ->reject(fn (array $item): bool => (string) ($item['id'] ?? '') === $archiveId)
            ->prepend($archive)
            ->take(20)
            ->values()
            ->all();
        $this->copilotActiveArchiveId = null;
    }

    public function viewCopilotArchive(string $archiveId): void
    {
        $archive = collect($this->copilotArchives)
            ->first(fn (array $item): bool => (string) ($item['id'] ?? '') === $archiveId);

        if (! is_array($archive)) {
            return;
        }

        $this->copilotActiveArchiveId = $archiveId;
        $this->copilotMessages = array_values((array) ($archive['messages'] ?? []));
        $this->copilotMessage = '';
        $this->persistCopilot();
    }

    public function continueCopilotArchive(string $archiveId): void
    {
        $archive = collect($this->copilotArchives)
            ->first(fn (array $item): bool => (string) ($item['id'] ?? '') === $archiveId);

        if (! is_array($archive)) {
            return;
        }

        $this->copilotSessionId = 'MKT-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(3)));
        $this->copilotActiveArchiveId = $archiveId;
        $this->copilotMessages = array_values((array) ($archive['messages'] ?? []));
        $this->copilotMessages[] = [
            'role' => 'assistant',
            'content' => 'Contexto da campanha '.$archive['campaign'].' reaberto. Pode continuar a partir daqui.',
            'at' => now()->toISOString(),
            'model' => 'marketing-ia-orchestrator',
            'execution_id' => null,
        ];
        $this->copilotMessage = '';
        $this->persistCopilot();
    }

    public function releaseCopilotChat(): void
    {
        $this->copilotSessionId = 'MKT-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(3)));
        $this->copilotMessages = [];
        $this->copilotMessage = '';
        $this->copilotActiveArchiveId = null;
        $this->copilotError = null;
        $this->persistCopilot();
    }

    private function persistCopilot(): void
    {
        $key = 'marketing_copilot.'.$this->marketingContextKey;
        session([
            $key.'.session_id' => $this->copilotSessionId,
            $key.'.messages' => $this->copilotMessages,
            $key.'.archives' => $this->copilotArchives,
            $key.'.active_archive_id' => $this->copilotActiveArchiveId,
        ]);
    }

    public function switchMarketingContext(string $contextKey): void
    {
        $contexts = (array) config('marketing_agents.contexts', []);
        if (! array_key_exists($contextKey, $contexts)) {
            $this->copilotError = 'Contexto de Marketing IA inválido.';
            return;
        }

        $this->marketingContextKey = $contextKey;
        session(['marketing_context.key' => $contextKey]);
        $this->redirect(static::getUrl());
    }

    public function getMarketingContext(): array
    {
        $contexts = (array) config('marketing_agents.contexts', []);

        return (array) ($contexts[$this->marketingContextKey] ?? $contexts['tv_sumare_client'] ?? []);
    }

    public function getMarketingContexts(): array
    {
        return (array) config('marketing_agents.contexts', []);
    }

    public function getAgents(): array
    {
        return app(AgentRegistry::class)->all();
    }

    private function creationDirectiveText(array $keys): string
    {
        $directives = (array) config('marketing_agents.creation_directives', []);
        $selected = [];

        foreach ($keys as $key) {
            $instruction = trim((string) data_get($directives, $key.'.instruction', ''));
            if ($instruction !== '') {
                $selected[] = strtoupper(str_replace('_', ' ', (string) $key)).': '.$instruction;
            }
        }

        return implode(' ', $selected);
    }

    public function getRuntime(): array
    {
        $hub = (array) config('marketing_agents.hub', []);

        return [
            'approval_mode' => (string) config('marketing_agents.approval_mode', 'unknown'),
            'schema_version' => (string) config('marketing_agents.schema_version', 'unknown'),
            'gemini_configured' => filled($hub['token'] ?? null),
            'strategy_enabled' => (bool) ($hub['strategy_enabled'] ?? false),
            'model' => 'Centro IA / roteamento dinâmico',
        ];
    }

    public function getMediaOrchestratorStatus(): array
    {
        return Cache::remember('marketing:centro-ia:orchestrator-status', 60, function (): array {
            $hub = (array) config('marketing_agents.hub', []);
            $url = trim((string) ($hub['url'] ?? ''));
            $token = trim((string) ($hub['token'] ?? ''));
            $projectId = trim((string) ($hub['project_id'] ?? 'vitrine-marketing-agents-core'));

            if ($url === '' || $token === '') {
                return ['ok' => false, 'error' => 'centro_ia_not_configured'];
            }

            $statusUrl = preg_replace('#/execute/?$#', '/orchestrator-status', $url);
            if (! is_string($statusUrl) || $statusUrl === $url) {
                return ['ok' => false, 'error' => 'centro_ia_status_url_invalid'];
            }

            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->withToken($token)
                    ->withHeaders(['X-Vitrine-Project' => $projectId])
                    ->timeout(20)
                    ->retry(1, 250, throw: false)
                    ->post($statusUrl, [
                        'project_id' => $projectId,
                        'quality_profile' => 'balanced',
                    ]);

                if (! $response->successful() || ! $response->json('ok')) {
                    return [
                        'ok' => false,
                        'error' => (string) ($response->json('error') ?? 'orchestrator_status_failed'),
                        'http_status' => $response->status(),
                    ];
                }

                return (array) $response->json();
            } catch (Throwable $exception) {
                report($exception);

                return [
                    'ok' => false,
                    'error' => 'orchestrator_status_exception',
                ];
            }
        });
    }

    public function getCampaignState(): array
    {
        return app(MarketingDashboardStateReader::class)->latest();
    }

    public function getPipeline(): array
    {
        return [
            ['label' => 'Estratégia', 'agents' => ['product_market_strategist']],
            ['label' => 'Planejamento', 'agents' => ['campaign_planner']],
            ['label' => 'Copy', 'agents' => ['copy_content']],
            ['label' => 'Criação', 'agents' => ['creative_director', 'video_producer']],
            ['label' => 'Distribuição', 'agents' => ['social_distribution']],
            ['label' => 'QA', 'agents' => ['qa_brand_guardian']],
        ];
    }
}

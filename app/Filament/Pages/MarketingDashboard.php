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
    public ?string $copilotError = null;

    public string $flowCampaign = 'Vitrine Social Mídia';
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
    public array $flowJobs = [];

    public string $nativeProductionStatus = 'RASCUNHO';
    public string $nativeProductionJobRef = '';
    public string $nativeProductionAssetUrl = '';
    public string $nativeProductionFinalPath = '';
    public string $nativeProductionPreviewUrl = '';
    public ?string $nativeProductionError = null;

    public function mount(): void
    {
        $this->copilotSessionId = (string) session('marketing_copilot.session_id', 'MKT-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(3))));
        $this->copilotMessages = array_values((array) session('marketing_copilot.messages', []));
        $this->persistCopilot();

        $productionState = (array) session('marketing_workstation.production', []);
        $legacyState = (array) session('marketing_workstation.flow', []);
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
        $this->flowJobs = $this->normalizeProductionJobs(array_values((array) ($workstation['jobs'] ?? [])));
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
        $this->flowToolName = 'Marketing IA Native Studio';
        $this->flowProjectName = trim((string) ($nativeStudio['official_project_name'] ?? 'Vitrine Social Mídia')) ?: 'Vitrine Social Mídia';
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

            if ($url === '' || $token === '') {
                throw new \RuntimeException('Centro IA não configurado para o Marketing IA.');
            }

            $context = collect($history)
                ->map(static fn (array $item): string => strtoupper((string) ($item['role'] ?? 'user')).': '.(string) ($item['content'] ?? ''))
                ->implode("\n\n");

            $system = 'Você é o Diretor de Marketing IA da Vitrine IA Pro dentro do Centro Operacional de Marketing. '
                .'Atue como copiloto operacional, em português do Brasil. Organize estratégia, campanha, copy, criativos, vídeo, distribuição e QA. '
                .'Nunca afirme que publicou, agendou, ativou campanha ou gastou verba sem uma ação operacional confirmada. '
                .'Publicação orgânica deve ir ao Metricool somente após aprovação humana. '
                .'Mídia paga deve ir ao Windsor.ai FB Ads/Meta Ads somente após aprovação humana e autorização explícita de orçamento/ativação. '
                .'Não invente preços, clientes, depoimentos, métricas ou funcionalidades.';

            $userPrompt = $context === '' ? $message : "Histórico recente:\n{$context}\n\nNova mensagem:\n{$message}";

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

            if (! $response->successful() || ! $response->json('ok')) {
                throw new \RuntimeException('O Centro IA não concluiu a solicitação.');
            }

            $reply = trim((string) $response->json('output_text'));
            if ($reply === '') {
                throw new \RuntimeException('O Centro IA retornou uma resposta vazia.');
            }

            $this->copilotMessages[] = [
                'role' => 'assistant',
                'content' => $reply,
                'at' => now()->toISOString(),
                'model' => (string) ($response->json('model') ?: 'hub-routed'),
                'execution_id' => $response->json('execution_id'),
            ];
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

            $system = 'Você é o Creative Director do Fluxo de Produção nativo do Marketing IA da Vitrine IA Pro. '
                .'Sua função é preparar um Job de Produção executável pelos motores nativos do Marketing IA, incluindo Gemini, Veo e finalização técnica. '
                .'Não afirme que gerou mídia, publicou ou consumiu créditos sem execução operacional confirmada. '
                .'Entregue um briefing implementável e objetivo, em português do Brasil, preservando fatos fornecidos e sem inventar logos, preços, depoimentos ou funcionalidades. '
                .'Quando a campanha ou produto for Vitrine Social Mídia, a comunicação deve deixar explícito que o assunto é redes sociais, produção de conteúdo, calendário editorial, Instagram/Facebook ou presença digital. Não use metáforas ambíguas como "vitrine parada", "vitrine estagnada" ou equivalentes sem explicar imediatamente que se trata das redes sociais. '
                .'O CTA deve ser exatamente o informado no briefing; se estiver vazio, marque CTA COMO NECESSÁRIO em vez de inventar "Assine agora" ou outra chamada. '
                .'Não gere, redesenhe nem interprete o logo da Vitrine IA Pro. Em ASSETS NECESSÁRIOS, sempre registre "logo oficial Vitrine IA Pro". Se o logo precisar fazer parte da cena, exija o arquivo oficial como imagem de referência; para assinatura de marca/watermark, indique aplicação em pós-produção pelo Marketing IA. '
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
                .'O logo oficial será fornecido/aplicado pelo Marketing IA; não peça ao gerador para recriá-lo.';

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
        $prompt = 'Crie um vídeo publicitário profissional para o produto Vitrine Social Mídia. '
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
                ->whereIn('slug', ['google', 'gemini', 'google-gemini'])
                ->where('status', 'ativo')
                ->first();

            if (! $agent || ! $provider) {
                throw new \RuntimeException('Marketing IA ou provider Google/Gemini não está disponível para geração de imagem.');
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
        $this->flowError = null;
        $allowed = [
            'RASCUNHO',
            'PREPARADO',
            'PRONTO_PARA_FLOW',
            'PRONTO_PARA_PRODUCAO',
            'EM_GERACAO',
            'GERADO',
            'EM_QA',
            'REPROVADO_QA',
            'APROVADO',
            'ENVIADO_DRIVE',
            'AGENDADO',
            'PUBLICADO',
        ];

        if (! in_array($status, $allowed, true)) {
            $this->flowError = 'Status de FLOW JOB inválido.';
            return;
        }

        if ($this->flowJobId === '') {
            $this->flowError = 'Gere um pacote para criar o FLOW JOB antes de alterar o status.';
            return;
        }

        if (in_array($status, ['PRONTO_PARA_FLOW', 'EM_GERACAO'], true) && ! $this->hasValidFlowToolUrl()) {
            $this->flowError = 'Cadastre uma URL oficial do Google Flow antes de enviar o job para geração.';
            return;
        }

        $this->flowJobStatus = $status;
        $this->upsertCurrentFlowJob();
        $this->persistFlowWorkstation();
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
            'tool_name' => $this->flowToolName,
            'project_name' => $this->flowProjectName,
            'generation_source' => $this->flowGenerationSource,
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

    private function persistFlowWorkstation(): void
    {
        session([
            'marketing_workstation.production' => [
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
            ],
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

    private function persistCopilot(): void
    {
        session([
            'marketing_copilot.session_id' => $this->copilotSessionId,
            'marketing_copilot.messages' => $this->copilotMessages,
        ]);
    }

    public function getAgents(): array
    {
        return app(AgentRegistry::class)->all();
    }

    public function getRuntime(): array
    {
        $hub = (array) config('marketing_agents.hub', []);

        return [
            'approval_mode' => (string) config('marketing_agents.approval_mode', 'unknown'),
            'schema_version' => (string) config('marketing_agents.schema_version', 'unknown'),
            'gemini_configured' => filled($hub['token'] ?? null),
            'strategy_enabled' => (bool) ($hub['strategy_enabled'] ?? false),
            'model' => 'Centro IA / Gemini',
        ];
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

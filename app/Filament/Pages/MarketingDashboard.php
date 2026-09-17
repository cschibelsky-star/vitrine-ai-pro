<?php

namespace App\Filament\Pages;

use App\Marketing\Application\MarketingDashboardStateReader;
use App\Marketing\Domain\Agents\AgentRegistry;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
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

    public function mount(): void
    {
        $this->copilotSessionId = (string) session('marketing_copilot.session_id', 'MKT-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(3))));
        $this->copilotMessages = array_values((array) session('marketing_copilot.messages', []));
        $this->persistCopilot();

        $workstation = (array) session('marketing_workstation.flow', []);
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
        $this->flowJobs = array_values((array) ($workstation['jobs'] ?? []));

        $flowBridge = (array) config('marketing_agents.flow_bridge', []);
        $this->flowToolName = trim((string) ($flowBridge['official_tool_name'] ?? 'Vitrine Content Studio')) ?: 'Vitrine Content Studio';
        $this->flowProjectName = trim((string) ($flowBridge['official_project_name'] ?? 'Vitrine Social Mídia')) ?: 'Vitrine Social Mídia';
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

            $system = 'Você é o Creative Director da Google AI Workstation da Vitrine IA Pro. '
                .'Sua função é preparar um pacote de produção para uso manual no Google Flow. '
                .'Não afirme que abriu o Flow, gerou mídia, publicou ou consumiu créditos. '
                .'Entregue um briefing implementável e objetivo, em português do Brasil, preservando fatos fornecidos e sem inventar logos, preços, depoimentos ou funcionalidades. '
                .'Quando a campanha ou produto for Vitrine Social Mídia, a comunicação deve deixar explícito que o assunto é redes sociais, produção de conteúdo, calendário editorial, Instagram/Facebook ou presença digital. Não use metáforas ambíguas como "vitrine parada", "vitrine estagnada" ou equivalentes sem explicar imediatamente que se trata das redes sociais. '
                .'O CTA deve ser exatamente o informado no briefing; se estiver vazio, marque CTA COMO NECESSÁRIO em vez de inventar "Assine agora" ou outra chamada. '
                .'Não gere, redesenhe nem interprete o logo da Vitrine IA Pro. Em ASSETS NECESSÁRIOS, sempre registre "logo oficial Vitrine IA Pro". Se o logo precisar fazer parte da cena, exija o arquivo oficial como imagem de referência; para assinatura de marca/watermark, indique aplicação em pós-produção pelo Marketing IA. '
                .'Não inclua marcas, logotipos ou produtos identificáveis de terceiros sem que tenham sido fornecidos como asset autorizado. Evite texto duplicado e determine uma única ocorrência por mensagem na tela. '
                .'Estruture obrigatoriamente em: FLOW JOB, DIREÇÃO CRIATIVA, CENA 01, CENA 02 quando necessária, CÂMERA, ÁUDIO, TEXTO NA TELA, NEGATIVE PROMPT, ASSETS NECESSÁRIOS e QA CHECKLIST. '
                .'No QA CHECKLIST, valide clareza sobre redes sociais, ausência de texto duplicado, ausência de marcas de terceiros, CTA fiel ao briefing e uso do logo oficial somente por asset/pós-produção. '
                .'Os prompts visuais devem estar prontos para copiar no Google Flow e devem respeitar o formato solicitado.';

            $userPrompt = "Campanha: {$campaign}\n"
                ."Objetivo: {$objective}\n"
                ."Público: {$audience}\n"
                ."Formato: {$formats[$this->flowFormat]}\n"
                ."Duração desejada: {$this->flowDuration}\n"
                ."Mensagem principal: {$message}\n"
                ."CTA: {$cta}\n"
                ."Estilo: {$style}\n\n"
                .'Prepare o pacote de produção para Google Flow. Se faltar algum asset de marca, marque como necessário em vez de inventar. '
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
                    logger()->warning('Flow Bridge: Centro IA indisponível; acionando fallback Gemini local.', [
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
                throw new \RuntimeException('Não foi possível gerar o pacote para o Google Flow.');
            }

            $this->flowPackage = $package;
            $this->createFlowJob('PREPARADO');
            $this->persistFlowWorkstation();
        } catch (Throwable $exception) {
            report($exception);
            $this->flowError = $exception->getMessage();
        }
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
        $model = trim((string) config('marketing_agents.flow_bridge.gemini_model', 'gemini-3.5-flash'));

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
        $this->flowJobId = 'FLOW-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(2)));
        $this->flowJobStatus = $this->hasValidFlowToolUrl() && $status === 'PREPARADO'
            ? 'PRONTO_PARA_FLOW'
            : $status;
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
            'marketing_workstation.flow' => [
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
            ],
        ]);
    }

    public function newCopilotSession(): void
    {
        $this->copilotSessionId = 'MKT-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(3)));
        $this->copilotMessages = [];
        $this->copilotMessage = '';
        $this->copilotError = null;
        $this->persistCopilot();
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

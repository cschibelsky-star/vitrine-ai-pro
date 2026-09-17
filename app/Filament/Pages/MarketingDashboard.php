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

            if ($url === '' || $token === '') {
                throw new \RuntimeException('Centro IA não configurado para a Google AI Workstation.');
            }

            $system = 'Você é o Creative Director da Google AI Workstation da Vitrine IA Pro. '
                .'Sua função é preparar um pacote de produção para uso manual no Google Flow. '
                .'Não afirme que abriu o Flow, gerou mídia, publicou ou consumiu créditos. '
                .'Entregue um briefing implementável e objetivo, em português do Brasil, preservando fatos fornecidos e sem inventar logos, preços, depoimentos ou funcionalidades. '
                .'Estruture obrigatoriamente em: FLOW JOB, DIREÇÃO CRIATIVA, CENA 01, CENA 02 quando necessária, CÂMERA, ÁUDIO, TEXTO NA TELA, NEGATIVE PROMPT, ASSETS NECESSÁRIOS e QA CHECKLIST. '
                .'Os prompts visuais devem estar prontos para copiar no Google Flow e devem respeitar o formato solicitado.';

            $userPrompt = "Campanha: {$campaign}\n"
                ."Objetivo: {$objective}\n"
                ."Público: {$audience}\n"
                ."Formato: {$formats[$this->flowFormat]}\n"
                ."Duração desejada: {$this->flowDuration}\n"
                ."Mensagem principal: {$message}\n"
                ."CTA: {$cta}\n"
                ."Estilo: {$style}\n\n"
                .'Prepare o pacote de produção para Google Flow. Se faltar algum asset de marca, marque como necessário em vez de inventar.';

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

            if (! $response->successful() || ! $response->json('ok')) {
                throw new \RuntimeException('O Centro IA não concluiu o pacote para o Google Flow.');
            }

            $package = trim((string) $response->json('output_text'));
            if ($package === '') {
                throw new \RuntimeException('O Centro IA retornou um pacote vazio.');
            }

            $this->flowPackage = $package;
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
        $this->flowError = null;
        $this->persistFlowWorkstation();
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

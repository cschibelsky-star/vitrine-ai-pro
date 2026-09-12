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
    protected static string $view = 'filament.pages.marketing-dashboard';

    public string $copilotMessage = '';
    public string $copilotSessionId = '';
    public array $copilotMessages = [];
    public ?string $copilotError = null;

    public function mount(): void
    {
        $this->copilotSessionId = (string) session('marketing_copilot.session_id', 'MKT-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(3))));
        $this->copilotMessages = array_values((array) session('marketing_copilot.messages', []));
        $this->persistCopilot();
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

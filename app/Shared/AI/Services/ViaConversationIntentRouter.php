<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ViaConversationIntentRouter
{
    public function resolve(string $message, Request $request): ?array
    {
        $normalized = Str::of($message)
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();

        if ($normalized === '') {
            return null;
        }

        $page = $this->resolvePageContext($request);
        $context = (array) $request->input('context', []);

        if ($this->asksPrepareAnalysis($normalized)) {
            $operational = (array) ($context['operational'] ?? []);
            $summary = trim((string) ($operational['summary'] ?? ''));
            $entity = (array) ($operational['entity'] ?? []);
            $screen = (array) ($context['screen'] ?? []);
            $screenFields = array_values(array_filter((array) ($screen['fields'] ?? []), 'is_array'));
            $screenText = trim((string) ($screen['text'] ?? ''));

            $pageIdentityAvailable = trim((string) ($context['heading'] ?? $context['module'] ?? $context['page'] ?? '')) !== '';

            if ($summary !== '' || $entity !== [] || $screenFields !== [] || $screenText !== '' || $pageIdentityAvailable) {
                return [
                    'intent' => 'prepare_factory_analysis',
                    'content' => 'Posso preparar a análise discovery-first usando o contexto atual desta tela. Vou considerar o que já está registrado antes de propor arquitetura, build ou alteração. Se quiser complementar a demanda, descreva o objetivo, o que deve mudar e o resultado esperado.',
                    'metadata' => $this->metadata($request, $page) + [
                        'factory_intent' => true,
                        'context_available' => true,
                    ],
                ];
            }

            return [
                'intent' => 'prepare_factory_analysis',
                'content' => 'Claro. O que você deseja construir, alterar ou analisar? Descreva a necessidade e eu preparo a análise discovery-first antes de qualquer arquitetura, build, instalação ou publicação.',
                'metadata' => $this->metadata($request, $page) + [
                    'factory_intent' => true,
                    'context_available' => false,
                ],
            ];
        }

        if ($this->asksCurrentPage($normalized) || $this->asksPagePurpose($normalized)) {
            return [
                'intent' => $this->asksPagePurpose($normalized) ? 'page_purpose' : 'current_page',
                'content' => $this->pageAnswer($page, $this->asksPagePurpose($normalized)),
                'metadata' => $this->metadata($request, $page),
            ];
        }

        if ($this->asksCurrentData($normalized)) {
            return [
                'intent' => 'current_data',
                'content' => $this->currentDataAnswer($page, $context),
                'metadata' => $this->metadata($request, $page),
            ];
        }

        if ($this->asksAttention($normalized)) {
            return [
                'intent' => 'page_attention',
                'content' => $this->attentionAnswer($page, $context),
                'metadata' => $this->metadata($request, $page),
            ];
        }

        if ($this->asksMode($normalized)) {
            return [
                'intent' => 'observer_mode',
                'content' => 'Estou em modo OBSERVER. Posso ler o contexto disponível, analisar evidências e recomendar próximos passos. Escrita, deploy, alterações de banco ou infraestrutura continuam exigindo autorização explícita do owner.',
                'metadata' => [
                    'local_context' => true,
                    'mission_orchestrator_used' => false,
                ],
            ];
        }

        return null;
    }

    private function asksPrepareAnalysis(string $message): bool
    {
        foreach ([
            'preparar analise', 'prepare analise', 'preparar a analise', 'prepare a analise',
            'preparar analise com ia', 'prepare analise com ia', 'analisar esta demanda',
            'analisar essa demanda', 'analise esta demanda', 'analise essa demanda',
            'preparar discovery', 'discovery first',
        ] as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function asksCurrentPage(string $message): bool
    {
        foreach ([
            'que pagina voce esta', 'qual pagina voce esta', 'em que pagina voce esta',
            'que pagina estamos', 'qual pagina estamos', 'onde estamos', 'onde voce esta',
            'qual tela voce esta', 'que tela voce esta', 'qual tela estamos', 'que tela estamos',
        ] as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function asksPagePurpose(string $message): bool
    {
        foreach ([
            'o que tem nessa pagina', 'o que tem nesta pagina', 'o que essa pagina faz',
            'o que esta pagina faz', 'para que serve essa pagina', 'para que serve esta pagina',
            'o que posso fazer aqui', 'o que da para fazer aqui', 'me explique esta tela', 'me explique essa tela',
        ] as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function asksCurrentData(string $message): bool
    {
        foreach ([
            'o que voce esta vendo', 'o que esta vendo', 'o que aparece aqui', 'o que aparece nessa tela',
            'o que aparece nesta tela', 'que dados tem aqui', 'que dados voce ve', 'resuma esta tela',
            'resuma essa tela', 'me diga o que esta vendo',
        ] as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function asksAttention(string $message): bool
    {
        foreach ([
            'tem algo errado aqui', 'tem algum problema aqui', 'o que merece atencao aqui',
            'o que precisa de atencao', 'tem algum alerta nesta tela', 'tem algum alerta nessa tela',
            'tem risco aqui', 'alguma inconsistencia aqui', 'o que devo olhar aqui',
        ] as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function asksMode(string $message): bool
    {
        foreach ([
            'qual seu modo', 'que modo voce esta', 'voce esta em observer',
            'o que voce pode fazer', 'quais suas permissoes',
        ] as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function resolvePageContext(Request $request): array
    {
        $referer = (string) $request->headers->get('referer', '');
        $refererPath = $referer !== '' ? (string) (parse_url($referer, PHP_URL_PATH) ?: '') : '';
        $path = $refererPath !== '' ? $refererPath : '/' . ltrim($request->path(), '/');
        $context = (array) $request->input('context', []);
        $resource = strtolower(trim((string) ($context['resource'] ?? '')));
        $contextLabel = strtolower(trim(implode(' ', array_filter([
            (string) ($context['module'] ?? ''),
            (string) ($context['heading'] ?? ''),
            (string) ($context['title'] ?? ''),
            (string) ($context['page'] ?? ''),
        ]))));

        if ($resource === '' || $resource === 'unknown') {
            if (str_contains($contextLabel, 'command center') || str_contains($contextLabel, 'dashboard') || str_contains($contextLabel, 'centro operacional')) {
                $resource = 'dashboard';
            }
        }

        $catalog = [
            'via' => ['VIA · Conversa', 'IA Center', 'hub de conversa operacional da VIA em modo OBSERVER'],
            'dashboard' => ['Dashboard', 'Centro Operacional', 'visão executiva e operacional consolidada do ambiente'],
            'companies' => ['Clientes / Empresas', 'Clientes', 'cadastro e acompanhamento operacional das empresas atendidas'],
            'products' => ['Produtos', 'Produtos', 'catálogo de produtos disponíveis no ecossistema'],
            'plans' => ['Planos', 'Produtos', 'definição comercial e operacional dos planos vinculados aos produtos'],
            'modules' => ['Módulos', 'Produtos', 'catálogo de capacidades funcionais reutilizáveis dos produtos'],
            'plan-modules' => ['Módulos por Plano', 'Produtos', 'regras que determinam quais módulos pertencem a cada plano'],
            'company-modules' => ['Módulos por Cliente', 'Clientes', 'visão dos módulos efetivamente liberados para cada empresa'],
            'licenses' => ['Licenças', 'Licenças', 'controle de produto, plano, vigência, valor e situação das licenças'],
            'leads' => ['Leads / Oportunidades', 'Comercial', 'gestão do funil comercial e oportunidades em andamento'],
            'payments' => ['Cobranças / Pagamentos', 'Financeiro', 'acompanhamento de cobranças, vencimentos e liquidação'],
            'contracts' => ['Contratos', 'Financeiro', 'gestão dos contratos comerciais vinculados a clientes, produtos e planos'],
            'subscriptions' => ['Assinaturas', 'Financeiro', 'acompanhamento de recorrência e ciclo de cobrança'],
            'ai-agents' => ['Agentes IA', 'IA Center', 'cadastro e configuração dos agentes de inteligência artificial'],
            'ai-providers' => ['Provedores IA', 'IA Center', 'configuração dos provedores de modelos utilizados pelo sistema'],
            'ai-consumptions' => ['Consumo IA', 'IA Center', 'visão de uso, tokens, custos e consumo dos modelos'],
            'ai-executions' => ['Execuções IA', 'IA Center', 'histórico operacional das execuções de inteligência artificial'],
            'ai-queues' => ['Filas IA', 'IA Center', 'monitoramento das filas e processamento assíncrono de IA'],
            'ai-alerts' => ['Alertas IA', 'IA Center', 'acompanhamento de sinais e alertas gerados pela camada de IA'],
            'settings' => ['Configurações', 'Administração', 'parâmetros administrativos e operacionais do Centro Operacional'],
            'users' => ['Usuários', 'Administração', 'gestão de usuários e acessos administrativos'],
        ];

        $key = $this->detectPageKey($path, $resource, array_keys($catalog));
        $entry = $catalog[$key] ?? null;
        $fallbackTitle = trim((string) ($context['heading'] ?? $context['module'] ?? $context['title'] ?? ''));
        $fallbackTitle = $fallbackTitle !== '' ? $fallbackTitle : 'Página administrativa';

        return [
            'key' => $key,
            'path' => $path,
            'resource' => $resource !== '' ? $resource : null,
            'title' => $entry[0] ?? $fallbackTitle,
            'area' => $entry[1] ?? 'Centro Operacional',
            'purpose' => $entry[2] ?? 'área administrativa do Centro Operacional',
        ];
    }

    private function detectPageKey(string $path, string $resource, array $keys): string
    {
        if (str_contains($path, '/admin/via-conversation')) {
            return 'via';
        }

        if ($path === '/admin' || $path === '/admin/') {
            return 'dashboard';
        }

        foreach ($keys as $key) {
            if ($resource === $key || $resource === rtrim($key, 's')) {
                return $key;
            }
            if (str_contains($path, '/' . $key)) {
                return $key;
            }
        }

        return 'unknown';
    }

    private function pageAnswer(array $page, bool $includePurpose): string
    {
        $base = "Estamos na página {$page['title']}, dentro de {$page['area']} do Centro Operacional.";

        if (! $includePurpose) {
            return $base;
        }

        return $base . ' Ela serve para ' . $page['purpose'] . '. A VIA pode usar o contexto disponível desta tela para explicar dados, apontar inconsistências e recomendar próximos passos sem executar alterações automaticamente.';
    }

    private function currentDataAnswer(array $page, array $context): string
    {
        $operational = (array) ($context['operational'] ?? $context);
        $summary = trim((string) ($operational['summary'] ?? ''));
        $entity = (array) ($operational['entity'] ?? []);

        if ($summary !== '') {
            return "Na página {$page['title']}, o contexto operacional atual mostra: {$summary}";
        }

        if ($entity !== []) {
            $pairs = [];
            foreach ($entity as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $pairs[] = str_replace('_', ' ', (string) $key) . ': ' . (is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE));
            }

            if ($pairs !== []) {
                return "Na página {$page['title']}, consigo verificar este registro: " . implode('; ', array_slice($pairs, 0, 8)) . '.';
            }
        }

        $screen = (array) ($context['screen'] ?? []);
        $screenFields = array_values(array_filter((array) ($screen['fields'] ?? []), 'is_array'));
        if ($screenFields !== []) {
            $pairs = [];
            foreach (array_slice($screenFields, 0, 8) as $field) {
                $label = trim((string) ($field['label'] ?? 'campo'));
                $value = trim((string) ($field['value'] ?? ''));
                if ($value !== '') {
                    $pairs[] = $label . ': ' . $value;
                }
            }
            if ($pairs !== []) {
                return "Na página {$page['title']}, consigo ver estes dados preenchidos/visíveis: " . implode('; ', $pairs) . '.';
            }
        }

        $screenText = trim((string) ($screen['text'] ?? ''));
        if ($screenText !== '') {
            return "Na página {$page['title']}, a tela possui conteúdo visível disponível para análise. Trecho do contexto atual: " . mb_substr($screenText, 0, 900) . (mb_strlen($screenText) > 900 ? '…' : '');
        }

        return "Estou na página {$page['title']}, mas o contexto recebido não contém um registro, resumo operacional ou conteúdo visível suficiente. LACUNA_DE_EVIDENCIA: preciso que a tela forneça os dados do item atual para descrevê-los sem inferência.";
    }

    private function attentionAnswer(array $page, array $context): string
    {
        $operational = (array) ($context['operational'] ?? $context);
        $signals = array_values(array_filter((array) ($operational['signals'] ?? []), 'is_array'));

        if ($signals === []) {
            $summary = trim((string) ($operational['summary'] ?? ''));
            if ($summary !== '') {
                return "Na página {$page['title']}, os indicadores operacionais monitorados não apresentam sinal de atenção neste momento. Resumo verificado: {$summary}";
            }

            return "Na página {$page['title']}, não há sinal operacional explícito no contexto atual. Isso não prova ausência de problemas. LACUNA_DE_EVIDENCIA: nenhuma inconsistência concreta foi fornecida pela tela neste momento.";
        }

        $items = [];
        foreach (array_slice($signals, 0, 5) as $signal) {
            $severity = strtoupper((string) ($signal['severity'] ?? 'info'));
            $message = trim((string) ($signal['message'] ?? ''));
            $code = trim((string) ($signal['code'] ?? ''));
            if ($message === '') {
                continue;
            }
            $items[] = $severity . ($code !== '' ? " [{$code}]" : '') . ': ' . $message;
        }

        if ($items === []) {
            return "Na página {$page['title']}, existem sinais no contexto, mas eles não têm descrição suficiente para uma conclusão segura. LACUNA_DE_EVIDENCIA.";
        }

        $priorityItems = array_values(array_filter((array) ($operational['items'] ?? []), 'is_array'));
        $priorityText = '';

        if ($priorityItems !== []) {
            $labels = [];
            foreach (array_slice($priorityItems, 0, 5) as $priority) {
                $label = trim((string) ($priority['label'] ?? ''));
                if ($label === '') {
                    continue;
                }

                $date = trim((string) ($priority['date'] ?? ''));
                $days = $priority['days'] ?? null;
                $type = trim((string) ($priority['type'] ?? ''));
                $status = trim((string) ($priority['status'] ?? ''));
                $suffix = '';

                if (is_numeric($days)) {
                    $days = (int) $days;
                    $suffix = $days < 0 ? ' · atrasado/vencido há ' . abs($days) . ' dia(s)' : ' · em ' . $days . ' dia(s)';
                } elseif ($date !== '') {
                    $suffix = ' · ' . $date;
                } elseif ($type === 'company') {
                    $domain = trim((string) ($priority['domain'] ?? ''));
                    $suffix = $status === 'Suspenso' ? ' · cliente suspenso' : ($domain === '' ? ' · ativo sem domínio principal' : '');
                } elseif ($type === 'product') {
                    $plans = (int) ($priority['plans'] ?? 0);
                    $suffix = $status !== '' && $status !== 'Ativo' ? ' · status ' . $status : ($plans === 0 ? ' · ativo sem plano cadastrado' : '');
                } elseif ($type === 'subscription' && $status !== '') {
                    $suffix = ' · status ' . $status;
                }

                $labels[] = $label . $suffix;
            }

            if ($labels !== []) {
                $priorityText = ' Prioridades verificadas: ' . implode(' | ', $labels) . '.';
            }
        }

        return "Na página {$page['title']}, encontrei estes sinais objetivos: " . implode(' | ', $items) . $priorityText . ' Posso explicar o impacto e recomendar a prioridade sem executar alterações.';
    }

    private function metadata(Request $request, array $page): array
    {
        return [
            'local_context' => true,
            'route_name' => $request->route()?->getName(),
            'route_uri' => $request->route()?->uri(),
            'path' => $page['path'],
            'page_key' => $page['key'],
            'resource' => $page['resource'],
            'mission_orchestrator_used' => false,
        ];
    }
}

<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function has_col(string $table, string $col): bool {
    return Schema::hasTable($table) && Schema::hasColumn($table, $col);
}

function cols(string $table): array {
    return Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
}

function only_cols(string $table, array $data): array {
    $cols = cols($table);
    $out = [];
    foreach ($data as $k => $v) {
        if (in_array($k, $cols, true)) {
            $out[$k] = $v;
        }
    }
    if (in_array('created_at', $cols, true) && !isset($out['created_at'])) $out['created_at'] = now();
    if (in_array('updated_at', $cols, true)) $out['updated_at'] = now();
    return $out;
}

function upsert_slug(string $table, string $slug, array $data): ?int {
    if (!Schema::hasTable($table)) {
        echo "Tabela não encontrada: {$table}\n";
        return null;
    }

    if (!has_col($table, 'slug')) {
        echo "Tabela sem coluna slug: {$table}\n";
        return null;
    }

    $data['slug'] = $slug;
    $record = DB::table($table)->where('slug', $slug)->first();

    if ($record) {
        DB::table($table)->where('id', $record->id)->update(only_cols($table, $data));
        echo "Atualizado: {$table} / {$slug}\n";
        return (int) $record->id;
    }

    $id = DB::table($table)->insertGetId(only_cols($table, $data));
    echo "Criado: {$table} / {$slug}\n";
    return (int) $id;
}

function provider_id(string $slug): ?int {
    if (!Schema::hasTable('ai_providers')) return null;
    $row = DB::table('ai_providers')->where('slug', $slug)->first();
    return $row ? (int) $row->id : null;
}

/**
 * PROVEDORES
 */
$providers = [
    [
        'name' => 'OpenAI',
        'slug' => 'openai',
        'provider_type' => 'openai',
        'status' => 'ativo',
        'api_key' => null,
        'config' => json_encode(['model_default' => 'gpt-4o-mini'], JSON_UNESCAPED_UNICODE),
        'notes' => 'Provedor para atendimento comercial, suporte, diagnóstico e automações gerais.',
    ],
    [
        'name' => 'Gemini',
        'slug' => 'gemini',
        'provider_type' => 'gemini',
        'status' => 'ativo',
        'api_key' => null,
        'config' => json_encode(['model_default' => 'gemini-2.5-flash'], JSON_UNESCAPED_UNICODE),
        'notes' => 'Provedor para conteúdo, análise editorial, marketing e apoio operacional.',
    ],
    [
        'name' => 'HeyGen',
        'slug' => 'heygen',
        'provider_type' => 'heygen',
        'status' => 'ativo',
        'api_key' => null,
        'config' => json_encode(['uso' => 'Reporter IA / vídeos com avatar'], JSON_UNESCAPED_UNICODE),
        'notes' => 'Provedor premium para vídeos com avatar e Repórter IA.',
    ],
    [
        'name' => 'Manual / Interno',
        'slug' => 'manual-interno',
        'provider_type' => 'manual',
        'status' => 'ativo',
        'api_key' => null,
        'config' => json_encode(['uso' => 'execucao_interna_sem_api'], JSON_UNESCAPED_UNICODE),
        'notes' => 'Execução interna/manual sem consumo de API externa.',
    ],
];

foreach ($providers as $p) {
    upsert_slug('ai_providers', $p['slug'], $p);
}

/**
 * AGENTES
 */
$agents = [
    [
        'name' => 'Agente Comercial',
        'slug' => 'agente-comercial',
        'ai_provider_id' => provider_id('openai'),
        'type' => 'comercial',
        'product_scope' => 'ecossistema',
        'version' => '1.0',
        'model_name' => 'gpt-4o-mini',
        'status' => 'online',
        'is_internal' => true,
        'description' => 'Qualifica leads, identifica necessidade, recomenda produto/plano, gera resumo comercial e próximo passo.',
        'config' => json_encode(['entrada' => 'lead', 'saida' => 'diagnostico_comercial'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'name' => 'Agente Marketing',
        'slug' => 'agente-marketing',
        'ai_provider_id' => provider_id('gemini'),
        'type' => 'marketing',
        'product_scope' => 'ecossistema',
        'version' => '1.0',
        'model_name' => 'gemini-2.5-flash',
        'status' => 'online',
        'is_internal' => true,
        'description' => 'Cria campanhas, textos, calendário de publicações e orientações para anúncios.',
        'config' => json_encode(['entrada' => 'briefing', 'saida' => 'campanha'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'name' => 'Agente Editorial News',
        'slug' => 'agente-editorial-news',
        'ai_provider_id' => provider_id('gemini'),
        'type' => 'editorial',
        'product_scope' => 'portal-news',
        'version' => '1.0',
        'model_name' => 'gemini-2.5-flash',
        'status' => 'online',
        'is_internal' => true,
        'description' => 'Apoia pauta, revisão, expansão editorial, classificação e organização de notícias.',
        'config' => json_encode(['entrada' => 'rss/noticia', 'saida' => 'materia_editorial'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'name' => 'Agente Suporte',
        'slug' => 'agente-suporte',
        'ai_provider_id' => provider_id('openai'),
        'type' => 'suporte',
        'product_scope' => 'master',
        'version' => '1.0',
        'model_name' => 'gpt-4o-mini',
        'status' => 'online',
        'is_internal' => true,
        'description' => 'Classifica chamados, sugere resposta e encaminha prioridade operacional.',
        'config' => json_encode(['entrada' => 'chamado', 'saida' => 'triagem_suporte'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'name' => 'Agente Diagnóstico Cliente',
        'slug' => 'agente-diagnostico-cliente',
        'ai_provider_id' => provider_id('openai'),
        'type' => 'diagnostico',
        'product_scope' => 'ecossistema',
        'version' => '1.0',
        'model_name' => 'gpt-4o-mini',
        'status' => 'online',
        'is_internal' => true,
        'description' => 'Analisa a necessidade do cliente e recomenda solução, produto, plano e módulos.',
        'config' => json_encode(['entrada' => 'necessidade_cliente', 'saida' => 'recomendacao_produto'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'name' => 'Agente Conteúdo Institucional',
        'slug' => 'agente-conteudo-institucional',
        'ai_provider_id' => provider_id('gemini'),
        'type' => 'conteudo',
        'product_scope' => 'institucional',
        'version' => '1.0',
        'model_name' => 'gemini-2.5-flash',
        'status' => 'online',
        'is_internal' => true,
        'description' => 'Gera textos institucionais, páginas, propostas e materiais de apresentação.',
        'config' => json_encode(['entrada' => 'briefing', 'saida' => 'texto_institucional'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'name' => 'Agente Operacional Master',
        'slug' => 'agente-operacional-master',
        'ai_provider_id' => provider_id('manual-interno'),
        'type' => 'operacional',
        'product_scope' => 'master',
        'version' => '1.0',
        'model_name' => 'manual',
        'status' => 'online',
        'is_internal' => true,
        'description' => 'Acompanha implantação, licenças, módulos, filas, alertas e pendências do ecossistema.',
        'config' => json_encode(['entrada' => 'evento_operacional', 'saida' => 'acao_master'], JSON_UNESCAPED_UNICODE),
    ],
];

$agentIds = [];
foreach ($agents as $a) {
    $agentIds[$a['slug']] = upsert_slug('ai_agents', $a['slug'], $a);
}

/**
 * FILA INICIAL
 */
if (Schema::hasTable('ai_queues')) {
    upsert_slug('ai_queues', 'fila-inicial-diagnostico-comercial', [
        'name' => 'Fila inicial - Diagnóstico comercial',
        'slug' => 'fila-inicial-diagnostico-comercial',
        'ai_agent_id' => $agentIds['agente-comercial'] ?? null,
        'client_id' => null,
        'company_id' => null,
        'status' => 'Pendente',
        'priority' => 'Média',
        'payload' => json_encode([
            'origem' => 'seed_master_1_4_2',
            'tipo' => 'diagnostico_comercial',
            'objetivo' => 'validar execução inicial do Agente Comercial',
        ], JSON_UNESCAPED_UNICODE),
    ]);
}

/**
 * EXECUÇÃO INICIAL
 */
if (Schema::hasTable('ai_executions')) {
    upsert_slug('ai_executions', 'execucao-inicial-homologacao-centro-ia', [
        'name' => 'Execução inicial - Homologação Centro IA',
        'slug' => 'execucao-inicial-homologacao-centro-ia',
        'ai_agent_id' => $agentIds['agente-operacional-master'] ?? null,
        'status' => 'Concluído',
        'input' => 'Teste inicial de estrutura operacional.',
        'output' => 'Centro IA populado com provedores, agentes e registros-base.',
        'started_at' => now(),
        'finished_at' => now(),
    ]);
}

/**
 * ALERTA INICIAL
 */
if (Schema::hasTable('ai_alerts')) {
    upsert_slug('ai_alerts', 'centro-ia-em-homologacao-operacional', [
        'title' => 'Centro IA em homologação operacional',
        'name' => 'Centro IA em homologação operacional',
        'slug' => 'centro-ia-em-homologacao-operacional',
        'status' => 'Aberto',
        'level' => 'Info',
        'message' => 'Dados-base criados. Próximo teste: execução real do Agente Comercial IA.',
        'description' => 'Alerta inicial de homologação do Centro IA.',
    ]);
}

/**
 * MEMÓRIA INICIAL
 */
if (Schema::hasTable('ai_memories')) {
    upsert_slug('ai_memories', 'contexto-operacional-vitrine-ai-pro', [
        'title' => 'Contexto operacional Vitrine AI Pro',
        'name' => 'Contexto operacional Vitrine AI Pro',
        'slug' => 'contexto-operacional-vitrine-ai-pro',
        'status' => 'Ativo',
        'content' => 'Ecossistema com Centro Operacional Master, produtos SaaS, agentes IA e foco em geração de vendas, implantação e suporte.',
        'description' => 'Memória base do Centro IA.',
    ]);
}

/**
 * CONSUMO INICIAL
 */
if (Schema::hasTable('ai_consumptions')) {
    upsert_slug('ai_consumptions', 'consumo-inicial-seed-operacional', [
        'name' => 'Consumo inicial - seed operacional',
        'slug' => 'consumo-inicial-seed-operacional',
        'ai_provider_id' => provider_id('manual-interno'),
        'status' => 'Registrado',
        'tokens' => 0,
        'cost' => 0,
        'description' => 'Registro inicial sem consumo externo.',
    ]);
}

echo "\nMASTER 1.4.2 Centro IA Seed Fix aplicado com sucesso.\n";
echo "Provedores, agentes e registros iniciais foram criados/atualizados.\n";
echo "Agora rode cache e teste o dashboard.\n";

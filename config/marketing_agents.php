<?php

declare(strict_types=1);

use App\Marketing\Domain\Agents\AgentType;

return [
    'schema_version' => '1.0.0',
    'approval_mode' => 'assisted',

    /*
     * ⭐⭐ A EXECUÇÃO DE IA — a parte que faz os agentes TRABALHAREM.
     *
     * Tudo aqui vem do AMBIENTE, e nunca do código: a chave da API é segredo, e o
     * provedor muda (RoteirO, Invision, OpenRouter, Together, Ollama local) sem
     * que isso precise de deploy.
     *
     * ⭐ O FORMATO É O OPENAI (`/chat/completions`), porque RoteirO, Invision,
     * OpenRouter, Together, Groq e Ollama (com compatibilidade) falam todos ele. Um
     * adaptador só atende todos — trocar de provedor é trocar três valores.
     *
     * ⭐ `default_model` é o modelo usado quando o agente não declara um. Com ele,
     * dá para rodar a fábrica com um modelo só e ir ajustando agente por agente.
     *
     * ⚠️ `api_key` NUNCA vai para o Git. Fica no `.env`, que está no `.gitignore`.
     * O `.env.example` abaixo é só a documentação do formato.
     */
    'default_model' => env('IA_MODEL_PADRAO', 'grok-4'),

    'ai' => [
        'provider' => env('IA_PROVEDOR', ''),
        'base_url' => env('IA_URL_BASE', ''),
        'api_key' => env('IA_CHAVE', ''),
        'timeout_seconds' => (int) env('IA_TIMEOUT_SEGUNDOS', 120),

        /*
         * ⭐ A LISTA DE MODELOS é explícita, e não "deixa ele tentar". O Escritório
         * recusa um agente cujo modelo não está aqui, com mensagem clara — em vez
         * de mandar a requisição e receber um 404 difícil de entender.
         */
        'models' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IA_MODELOS', '')),
        ))),
    ],

    'agents' => [
        'marketing_director' => ['name' => 'Marketing Director', 'type' => AgentType::Orchestrator->value, 'version' => '1.0.0', 'enabled' => true, 'depends_on' => [], 'may_publish' => false, 'may_spend' => false, 'may_block_pipeline' => true, 'next_agents' => ['product_market_strategist']],
        'product_market_strategist' => ['name' => 'Product & Market Strategist', 'type' => AgentType::Specialist->value, 'version' => '1.0.0', 'enabled' => true, 'depends_on' => [], 'may_publish' => false, 'may_spend' => false, 'may_block_pipeline' => false, 'next_agents' => ['campaign_planner']],
        'campaign_planner' => ['name' => 'Campaign Planner', 'type' => AgentType::Specialist->value, 'version' => '1.0.0', 'enabled' => true, 'depends_on' => ['product_market_strategist'], 'may_publish' => false, 'may_spend' => false, 'may_block_pipeline' => false, 'next_agents' => ['copy_content']],
        'copy_content' => ['name' => 'Copy & Content Agent', 'type' => AgentType::Specialist->value, 'version' => '1.0.0', 'enabled' => true, 'depends_on' => ['product_market_strategist', 'campaign_planner'], 'may_publish' => false, 'may_spend' => false, 'may_block_pipeline' => false, 'next_agents' => ['creative_director', 'video_producer']],
        'creative_director' => ['name' => 'Creative Director', 'type' => AgentType::Specialist->value, 'version' => '1.0.0', 'enabled' => true, 'depends_on' => ['copy_content'], 'may_publish' => false, 'may_spend' => false, 'may_block_pipeline' => false, 'next_agents' => ['social_distribution']],
        'video_producer' => ['name' => 'Video Producer', 'type' => AgentType::Specialist->value, 'version' => '1.0.0', 'enabled' => true, 'depends_on' => ['copy_content'], 'may_publish' => false, 'may_spend' => false, 'may_block_pipeline' => false, 'next_agents' => ['social_distribution']],
        'social_distribution' => ['name' => 'Social & Distribution Agent', 'type' => AgentType::Specialist->value, 'version' => '1.0.0', 'enabled' => true, 'depends_on' => ['creative_director', 'video_producer'], 'may_publish' => false, 'may_spend' => false, 'may_block_pipeline' => false, 'next_agents' => ['qa_brand_guardian']],
        'qa_brand_guardian' => ['name' => 'QA & Brand Guardian', 'type' => AgentType::Validator->value, 'version' => '1.0.0', 'enabled' => true, 'depends_on' => ['social_distribution'], 'may_publish' => false, 'may_spend' => false, 'may_block_pipeline' => true, 'next_agents' => []],
        'performance_analyst' => ['name' => 'Performance Analyst', 'type' => AgentType::Analyst->value, 'version' => '1.0.0', 'enabled' => false, 'depends_on' => [], 'activation_condition' => 'campaign_has_metrics', 'may_publish' => false, 'may_spend' => false, 'may_block_pipeline' => false, 'next_agents' => []],
    ],
];

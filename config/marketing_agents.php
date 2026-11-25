<?php

declare(strict_types=1);

use App\Marketing\Domain\Agents\AgentType;

return [
    'schema_version' => '1.0.0',
    'approval_mode' => 'assisted',

    /*
     * ⭐⭐ A EXECUÇÃO DE IA — a parte que faz os agentes TRABALHAREM.
     *
     * ⭐⭐ A REGRA MAIS IMPORTANTE: ⭐ NÃO HÁ UMA SEGUNDA CONVENÇÃO DE VARIÁVEIS.
     *
     *   `ROTEIA_BASE_URL`, `ROTEIA_API_KEY` e `ROTEIA_CHAT_MODEL` são as MESMAS
     *   variáveis que o IA Center já usa (veja `AiExecutionService`). O Escritório
     *   LÊ AS MESMAS. Se o Escritório tivesse as suas próprias, teríamos DUAS
     *   chaves para o mesmo provedor — e o dia que uma acabasse e a outra não, o
     *   produto quebraria de um jeito difícil de diagnosticar.
     *
     *   ⭐ O que é NOVO aqui são só duas coisas, e são as que ainda não existiam:
     *   a LISTA de modelos aceitos e o MODELO padrão dos agentes.
     *
     * ⭐ O FORMATO É O OPENAI (`/chat/completions`), porque é o protocolo que o
     * RoteirO já fala — e porque Invision, OpenRouter, Together, Groq e Ollama
     * (com compatibilidade) falam o mesmo. Um adaptador atende todos.
     *
     * ⭐ `default_model` é o modelo usado quando o agente não declara um. Com ele,
     * dá para rodar a fábrica com um modelo só e ir ajustando agente por agente.
     *
     * ⚠️ `api_key` NUNCA vai para o Git. Fica no `.env`, que está no `.gitignore`.
     */
    'default_model' => env('ESCRITORIO_MODEL_PADRAO', ''),

    'ai' => [
        'provider' => env('ESCRITORIO_IA_PROVEDOR', 'roteia'),

        // ⭐⭐ As MESMAS variáveis do IA Center. Não invente novas.
        'base_url' => env('ROTEIA_BASE_URL', ''),
        'api_key' => env('ROTEIA_API_KEY', ''),
        'timeout_seconds' => (int) env('ROTEIA_TIMEOUT_SEGUNDOS', 120),

        // ⭐ O MODELO DO GATEWAY — a mesma variável que o IA Center já usa.
        // É o último recurso na resolução do modelo do agente.
        'model_default' => env('ROTEIA_CHAT_MODEL', ''),

        /*
         * ⭐ A LISTA DE MODELOS é explícita, e não "deixa ele tentar". O Escritório
         * recusa um agente cujo modelo não está aqui, com mensagem clara — em vez
         * de mandar a requisição e receber um 404 difícil de entender.
         *
         * ⭐ É aqui que entra a lista REAL de modelos do RoteirO. Enquanto ela
         * estiver vazia, o provedor não é registrado e o Escritório segue
         * funcionando sem execução automática — que é o estado certo para
         * medir fluxo e custo antes de ligar a IA.
         */
        'models' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('ESCRITORIO_IA_MODELOS', '')),
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

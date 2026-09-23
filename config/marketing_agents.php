<?php

declare(strict_types=1);

use App\Marketing\Domain\Agents\AgentType;

return [
    'schema_version' => '1.0.0',
    'approval_mode' => 'assisted',
    'contexts' => [
        'tv_digital_engine' => [
            'mode' => 'engine',
            'label' => 'Motor TV Digital',
            'tenant_key' => 'tv_digital_enterprise',
            'client_key' => null,
            'brand' => 'TV Digital Enterprise',
            'purpose' => 'Capacidade interna do produto TV Digital para transformar conteúdo editorial em peças, vídeos e distribuição.',
        ],
        'tv_sumare_client' => [
            'mode' => 'client',
            'label' => 'Cliente TV Sumaré',
            'tenant_key' => 'marketing_ia',
            'client_key' => 'tv_sumare',
            'brand' => 'TV Sumaré',
            'purpose' => 'Operação de marketing e redes sociais da marca TV Sumaré como cliente independente do Marketing IA.',
        ],
    ],
    'hub' => [
        'strategy_enabled' => env('MARKETING_HUB_STRATEGY_ENABLED', false),
        'url' => env('CENTRO_IA_URL', 'http://vitrine_core_web_hml/api/internal/centro-ia/execute'),
        'token' => env('CENTRO_IA_INTERNAL_TOKEN'),
        'project_id' => env('CENTRO_IA_PROJECT_ID', 'vitrine-marketing-agents-core'),
        'capability' => env('CENTRO_IA_CAPABILITY', 'marketing_generation'),
        'timeout' => (int) env('CENTRO_IA_TIMEOUT', 60),
    ],
    'creation_directives' => [
        'marketing_briefing' => [
            'applies_to' => ['campaign_planner', 'copy_content', 'creative_director'],
            'instruction' => 'Antes de criar, normalize o pedido em briefing: produto/marca, objetivo, público, canais, formatos, mensagem principal, CTA, tom, restrições e assets disponíveis. Não invente dados ausentes. Sinalize lacunas críticas antes de assumir informações.',
        ],
        'creative_direction' => [
            'applies_to' => ['creative_director', 'video_producer'],
            'instruction' => 'Defina conceito central, direção visual, narrativa, composição, ritmo e coerência entre peças. Decida o que criar, nunca qual provedor/modelo executar. Não fixe Veo, Gemini, Grok, Seedream, Seedance ou equivalente; essa decisão pertence ao Centro IA.',
        ],
        'brand_asset_guard' => [
            'applies_to' => ['creative_director', 'video_producer', 'qa_brand_guardian'],
            'instruction' => 'Use somente identidade, logos, imagens e claims autorizados. Nunca redesenhe ou invente logotipo. Quando o asset oficial não estiver disponível, marque-o como necessário e preserve área segura para aplicação em pós-produção. Não introduza marcas de terceiros sem autorização.',
        ],
        'marketing_qa' => [
            'applies_to' => ['qa_brand_guardian'],
            'instruction' => 'Valide aderência ao briefing, clareza da mensagem, CTA, formato/canal, consistência de marca, ausência de duplicação, claims suportados e uso correto de assets. Reprovar somente o item com problema e pedir revisão localizada, preservando o restante aprovado.',
        ],
    ],
    'creation_pipeline' => [
        'stages' => ['briefing', 'creative_direction', 'brand_guard', 'generation', 'qa', 'approval', 'distribution'],
        'model_selection_owner' => 'centro_ia',
        'revision_mode' => 'localized_artifact_version',
    ],
    'publisher' => [
        'default' => env('MARKETING_PUBLISHER', 'meta_direct'),
        'meta' => [
            'base_url' => env('META_GRAPH_BASE_URL', 'https://graph.instagram.com'),
            'graph_version' => env('META_GRAPH_VERSION'),
            'app_id' => env('META_APP_ID'),
            'app_secret' => env('META_APP_SECRET'),
            'redirect_uri' => env('META_OAUTH_REDIRECT_URI'),
            'access_token' => env('META_ACCESS_TOKEN'),
            'instagram_user_id' => env('META_INSTAGRAM_USER_ID'),
            'facebook_page_id' => env('META_FACEBOOK_PAGE_ID'),
            'scopes' => [
                'instagram_business_basic',
                'instagram_business_content_publish',
            ],
        ],
    ],
    'native_studio' => [
        'enabled' => env('MARKETING_NATIVE_STUDIO_ENABLED', true),
        'role' => 'creation_machine',
        'source_of_truth' => 'marketing_ia',
        'director_model' => env('MARKETING_STUDIO_DIRECTOR_MODEL', 'gemini-3.5-flash'),
        'image_provider' => env('MARKETING_STUDIO_IMAGE_PROVIDER', 'google'),
        'image_model' => env('MARKETING_STUDIO_IMAGE_MODEL', 'gemini-3.1-flash-image'),
        'video_provider' => env('MARKETING_STUDIO_VIDEO_PROVIDER', 'gemini_veo'),
        'video_model' => env('MARKETING_STUDIO_VIDEO_MODEL', 'veo-3.1-generate-preview'),
        'avatar_provider' => env('MARKETING_STUDIO_AVATAR_PROVIDER', 'heygen'),
        'official_project_name' => env('MARKETING_STUDIO_PROJECT_NAME', 'Vitrine Social Mídia'),
        'official_logo_asset' => env('MARKETING_STUDIO_LOGO_ASSET', 'LOGO_OFICIAL_VITRINE_IA_PRO'),
        'flow_dependency' => false,
        'approval_gates' => [
            'brief' => true,
            'voice_preview' => true,
            'avatar_selection' => true,
            'video_render' => true,
        ],
        'render_policy' => [
            'require_explicit_avatar' => true,
            'require_explicit_voice' => true,
            'allow_automatic_avatar_selection' => false,
            'allow_voice_speed_adjustment' => false,
            'default_voice_speed' => 1.0,
            'test_resolution' => '720p',
        ],
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

<?php

declare(strict_types=1);

use App\Marketing\Domain\Agents\AgentType;

return [
    'schema_version' => '1.0.0',
    'approval_mode' => 'assisted',
    'hub' => [
        'strategy_enabled' => env('MARKETING_HUB_STRATEGY_ENABLED', false),
        'url' => env('CENTRO_IA_URL', 'http://vitrine_core_web_hml/api/internal/centro-ia/execute'),
        'token' => env('CENTRO_IA_INTERNAL_TOKEN'),
        'project_id' => env('CENTRO_IA_PROJECT_ID', 'vitrine-marketing-agents-core'),
        'capability' => env('CENTRO_IA_CAPABILITY', 'marketing_generation'),
        'timeout' => (int) env('CENTRO_IA_TIMEOUT', 60),
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

<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Vertex AI',
                'provider_type' => 'vertex-ai',
                'status' => 'ativo',
                'notes' => 'Google Cloud Vertex AI para mídia elegível ao faturamento GCP. Só é roteado quando VERTEX_AI_ENABLED e autenticação do runtime estão configurados.',
                'config' => [
                    'capabilities' => ['image_generation', 'video_generation'],
                    'models' => [
                        'image_generation' => env('GOOGLE_VERTEX_IMAGE_MODEL', 'imagen-4.0-generate-001'),
                        'video_generation' => env('GOOGLE_VERTEX_VIDEO_MODEL', 'veo-3.0-generate-001'),
                    ],
                ],
            ],
            [
                'name' => 'Roteia',
                'provider_type' => 'roteia',
                'status' => 'ativo',
                'notes' => 'Gateway central preferencial do IA Center. Credenciais e endpoint são fornecidos exclusivamente pelo runtime.',
                'config' => [
                    'model_default' => env('ROTEIA_CHAT_MODEL', 'roteia-default'),
                    'capabilities' => ['marketing_strategy', 'copy', 'critical_review', 'image_generation', 'video_generation', 'avatar_video'],
                ],
            ],
            [
                'name' => 'Gemini',
                'provider_type' => 'gemini',
                'status' => 'ativo',
                'notes' => 'Google Gemini para estratégia, conteúdo, análise e geração de imagem.',
                'config' => [
                    'model_default' => 'gemini-3.6-flash',
                    'capabilities' => ['marketing_strategy', 'copy', 'critical_review', 'image_generation'],
                    'models' => [
                        'image_generation' => 'gemini-3.1-flash-image',
                    ],
                ],
            ],
            [
                'name' => 'OpenAI',
                'provider_type' => 'openai',
                'status' => 'ativo',
                'notes' => 'Provedor para agentes, assistentes, estratégia e revisão avançada.',
                'config' => [
                    'model_default' => 'gpt-4o-mini',
                    'capabilities' => ['marketing_strategy', 'copy', 'critical_review'],
                ],
            ],
            [
                'name' => 'HeyGen',
                'provider_type' => 'heygen',
                'status' => 'ativo',
                'notes' => 'Provedor especializado em avatar e vídeo com apresentador virtual.',
                'config' => [
                    'capabilities' => ['avatar_video'],
                ],
            ],
        ];

        foreach ($providers as $provider) {
            $config = $provider['config'];
            unset($provider['config']);

            AiProvider::updateOrCreate(
                ['slug' => Str::slug($provider['name'])],
                $provider + ['config' => $config]
            );
        }
    }
}

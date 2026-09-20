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

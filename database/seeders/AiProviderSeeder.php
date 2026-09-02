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
                'provider_type' => 'text',
                'status' => 'ativo',
                'notes' => 'Google Gemini para estratégia, conteúdo, análise e apoio editorial.',
                'config' => [
                    'model_default' => 'gemini-2.5-flash',
                    'capabilities' => ['marketing_strategy', 'copy', 'critical_review'],
                ],
            ],
            [
                'name' => 'OpenAI',
                'provider_type' => 'agents',
                'status' => 'ativo',
                'notes' => 'Provedor para agentes, assistentes, estratégia e revisão avançada.',
                'config' => [
                    'model_default' => 'gpt-4o-mini',
                    'capabilities' => ['marketing_strategy', 'copy', 'critical_review'],
                ],
            ],
            [
                'name' => 'HeyGen',
                'provider_type' => 'video',
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

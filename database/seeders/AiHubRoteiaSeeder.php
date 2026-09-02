<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shared\AI\Models\AiModel;
use App\Shared\AI\Models\AiProvider;
use Illuminate\Database\Seeder;

class AiHubRoteiaSeeder extends Seeder
{
    public function run(): void
    {
        $provider = AiProvider::query()->updateOrCreate(
            ['slug' => 'roteia'],
            [
                'name' => 'Roteia',
                'provider_type' => 'roteia',
                'status' => 'experimental',
                'config' => [
                    'driver' => 'openai-compatible',
                    'mode' => 'experimental',
                    'managed_by' => 'Vitrine IA Hub',
                ],
                'notes' => 'Provider inicial de homologação do Vitrine IA Hub. Não utilizar em produção crítica até homologação técnica, contratual e LGPD.',
            ]
        );

        $models = [
            // Texto
            ['Mistral Nemo', 'mistralai/mistral-nemo', 'text', 'economy', 'tokens', 131100, 0.14, 0.22, 0, ['text','chat']],
            ['Qwen3.7 Flash', 'qwen/qwen3.7-flash', 'text', 'economy', 'tokens', 1000000, 0.22, 0.97, 0, ['text','chat']],
            ['DeepSeek V4 Flash', 'deepseek/deepseek-v4-flash', 'text', 'economy', 'tokens', 1000000, 0.61, 1.23, 0, ['text','chat']],
            ['GPT-5.6 Luna', 'openai/gpt-5.6-luna', 'text', 'balanced', 'tokens', 1000000, 1.49, 8.91, 0, ['text','chat']],
            ['MiMo-V2.5-Pro', 'xiaomi/mimo-v2.5-pro', 'text', 'balanced', 'tokens', 1100000, 3.23, 6.46, 0, ['text','chat']],
            ['Gemini 3.7 Flash', 'google/gemini-3.7-flash', 'text', 'balanced', 'tokens', 1000000, 2.79, 13.93, 0, ['text','chat']],
            ['Claude Sonnet 5', 'anthropic/claude-sonnet-5', 'text', 'premium', 'tokens', 1000000, 14.86, 74.28, 0, ['text','chat']],
            ['GPT-5.6 Terra', 'openai/gpt-5.6-terra', 'text', 'premium', 'tokens', 1100000, 14.86, 89.13, 0, ['text','chat']],
            ['GPT-5.6 Sol', 'openai/gpt-5.6-sol', 'text', 'premium', 'tokens', 1000000, 18.57, 111.41, 0, ['text','chat']],

            // Embeddings
            ['Qwen3 Embedding 8B', 'qwen/qwen3-embedding-8b', 'embedding', 'economy', 'tokens', 32800, 0.07, 0, 0, ['embedding','rag']],
            ['BGE-M3', 'baai/bge-m3', 'embedding', 'economy', 'tokens', 8200, 0.07, 0, 0, ['embedding','rag','multilingual']],
            ['Text Embedding 3 Small', 'openai/text-embedding-3-small', 'embedding', 'balanced', 'tokens', 8200, 0.15, 0, 0, ['embedding','rag']],

            // Imagem
            ['GPT Image 1 Mini', 'openai/gpt-image-1-mini', 'image', 'economy', 'image', 400000, 0, 0, 0.08, ['image','generation']],
            ['FLUX.2 Klein 4B', 'black-forest-labs/flux.2-klein-4b', 'image', 'economy', 'image', 41000, 0, 0, 0.11, ['image','generation']],
            ['Nano Banana 2 Lite', 'google/gemini-3.1-flash-lite-image', 'image', 'balanced', 'image', 65500, 0, 0, 0.25, ['image','generation']],

            // Transcrição
            ['NVIDIA Parakeet TDT 0.6B v3', 'nvidia/parakeet-tdt-0.6b-v3', 'transcription', 'economy', 'minute', null, 0, 0, 0.01, ['audio','transcription']],
            ['Voxtral Mini Transcribe 2', 'mistral/voxtral-mini-transcribe-2', 'transcription', 'economy', 'minute', null, 0, 0, 0.02, ['audio','transcription']],
            ['GPT Transcribe', 'openai/gpt-transcribe', 'transcription', 'balanced', 'minute', null, 0, 0, 0.03, ['audio','transcription']],
        ];

        foreach ($models as [$name, $providerModelId, $modality, $tier, $billingUnit, $context, $inputCost, $outputCost, $unitCost, $capabilities]) {
            AiModel::query()->updateOrCreate(
                [
                    'ai_provider_id' => $provider->id,
                    'provider_model_id' => $providerModelId,
                ],
                [
                    'name' => $name,
                    'slug' => str($providerModelId)->slug()->toString(),
                    'modality' => $modality,
                    'tier' => $tier,
                    'billing_unit' => $billingUnit,
                    'context_window' => $context,
                    'input_cost_per_million' => $inputCost,
                    'output_cost_per_million' => $outputCost,
                    'unit_cost_brl' => $unitCost,
                    'capabilities' => $capabilities,
                    'metadata' => ['currency' => 'BRL', 'source' => 'Roteia catalog 2026-08-18'],
                    'is_active' => true,
                    'is_experimental' => true,
                ]
            );
        }
    }
}

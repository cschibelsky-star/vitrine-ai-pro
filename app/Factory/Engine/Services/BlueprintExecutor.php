<?php

declare(strict_types=1);

namespace App\Factory\Engine\Services;

use App\Factory\Engine\DTO\ExecutionOutput;
use App\Factory\Engine\Support\ContextBuilder;
use App\Factory\Engine\Support\PromptCompiler;
use App\Factory\Engine\Support\TokenEstimator;
use App\Factory\Models\FactoryExecution;

class BlueprintExecutor
{
    public function __construct(
        protected ContextBuilder $contextBuilder,
        protected PromptCompiler $promptCompiler,
        protected TokenEstimator $tokenEstimator,
        protected ProviderManager $providerManager,
    ) {
    }

    public function execute(FactoryExecution $execution): ExecutionOutput
    {
        $context = $this->contextBuilder->build($execution);
        $prompt = $this->promptCompiler->compile($context);
        $tokens = $this->tokenEstimator->estimate($prompt);

        $providerName = $execution->context['provider'] ?? 'internal';
        $provider = $this->providerManager->provider($providerName);

        $response = $provider->generate([
            'prompt' => $prompt,
            'context' => $context,
            'estimated_tokens' => $tokens,
        ]);

        return ExecutionOutput::success([
            'provider' => $response->provider,
            'content' => $response->content,
            'raw' => $response->raw,
            'usage' => $response->usage,
            'estimated_tokens' => $tokens,
        ], 'Execução finalizada pelo Factory Engine.');
    }
}

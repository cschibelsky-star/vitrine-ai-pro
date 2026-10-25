<?php

declare(strict_types=1);

namespace App\Services\Ai\Chat;

use App\Models\AiAgent;
use App\Models\AiExecution;
use App\Models\AiMediaGeneration;
use App\Services\Ai\AiRoutingService;
use InvalidArgumentException;

/**
 * Domain entrypoint for the Vitrine AI Chat.
 *
 * Provider credentials, model routing and cost accounting remain owned by
 * Centro IA; callers must authorize users and resolve an allowed AiAgent.
 */
final class AiChatOrchestrator
{
    public function __construct(
        private readonly AiRoutingService $routing,
        private readonly RequestBudgetGuard $budgetGuard,
    )
    {
    }

    public function send(
        AiAgent $agent,
        string $message,
        ?string $capability = 'copy',
        array $budgetRequest = [],
        array $verifiedQuote = [],
    ): array
    {
        $message = trim($message);
        if ($message === '' || mb_strlen($message) > 120000) {
            throw new InvalidArgumentException('Chat message must contain 1 to 120000 characters.');
        }

        // Only chat-oriented capabilities are allowed here. Media generation
        // follows the dedicated Centro IA media flow instead.
        $allowed = ['copy', 'critical_review', 'marketing_strategy'];
        if (! in_array($capability, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported chat capability.');
        }

        // Fail closed: without trusted usage counts and verified price, no provider call.
        if ($budgetRequest === [] || $verifiedQuote === []) {
            return ['ok' => false, 'status' => 'blocked_preflight',
                'reason' => 'budget_or_quote_missing', 'execution_id' => null];
        }
        $decision = $this->budgetGuard->check($budgetRequest, $verifiedQuote);
        if ($decision['allowed'] !== true) {
            return ['ok' => false, 'status' => 'blocked_preflight',
                'reason' => $decision['reason'], 'execution_id' => null];
        }

        $execution = $this->routing->execute($agent, $message, $capability);

        return $this->normalize($execution);
    }

    /**
     * Sequential independent executions. No tool calls or repository writes.
     * The caller chooses permitted agents and owns the access-control checks.
     *
     * @param array<string, AiAgent> $agents
     * @return array<string, array<string, mixed>>
     */
    public function compare(
        array $agents,
        string $message,
        array $budgetRequest = [],
        array $verifiedQuote = [],
    ): array
    {
        if (count($agents) < 2 || count($agents) > 3) {
            throw new InvalidArgumentException('Comparison requires two or three agents.');
        }

        $results = [];
        foreach ($agents as $label => $agent) {
            if (! $agent instanceof AiAgent) {
                throw new InvalidArgumentException('Every comparison participant must be an AI agent.');
            }
            $results[(string) $label] = $this->send($agent, $message, 'copy', $budgetRequest, $verifiedQuote);
            if ($results[(string) $label]['ok'] !== true) {
                break; // Never fan out additional paid calls after a preflight/execution failure.
            }
        }

        return $results;
    }

    private function normalize(AiExecution|AiMediaGeneration $execution): array
    {
        return [
            'execution_id' => $execution->id,
            'status' => $execution->status,
            'model' => $execution->model_name ?? null,
            'output_text' => $execution->output ?? null,
            'ok' => $execution->status === 'Concluído',
        ];
    }
}

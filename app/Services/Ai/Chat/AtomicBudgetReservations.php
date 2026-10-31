<?php

declare(strict_types=1);

namespace App\Services\Ai\Chat;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\LockProvider;
use InvalidArgumentException;
use RuntimeException;

/**
 * Atomic reservation prototype for a single budget scope.
 * Requires a SHARED cache store supporting distributed locks (e.g. Redis).
 * This service does not perform billing or provider calls.
 */
final class AtomicBudgetReservations
{
    public function __construct(private readonly CacheRepository $cache)
    {
    }

    public function reserve(
        string $scope,
        string $requestId,
        int $amountMinor,
        int $limitMinor,
        int $ttlSeconds = 120
    ): array {
        if ($scope === '' || $requestId === '' || $amountMinor <= 0 ||
            $limitMinor < 0 || $ttlSeconds < 1 || $ttlSeconds > 3600) {
            throw new InvalidArgumentException('Invalid reservation arguments.');
        }
        if (! $this->cache->getStore() instanceof LockProvider) {
            throw new RuntimeException('Distributed locking cache store required.');
        }

        $lockKey = 'ai-budget:lock:'.hash('sha256', $scope);
        $stateKey = 'ai-budget:state:'.hash('sha256', $scope);
        $lock = $this->cache->lock($lockKey, 10);
        if (! $lock->get()) {
            return ['allowed' => false, 'reason' => 'budget_lock_unavailable'];
        }
        try {
            $state = $this->cache->get($stateKey, ['reservations' => []]);
            $now = time();
            $entries = array_filter(
                $state['reservations'] ?? [],
                static fn ($entry): bool => is_array($entry) && ($entry['expires_at'] ?? 0) > $now
            );
            if (array_key_exists($requestId, $entries)) {
                return ['allowed' => true, 'reason' => 'already_reserved'];
            }
            $held = array_sum(array_column($entries, 'amount_minor'));
            if ($held + $amountMinor > $limitMinor) {
                return ['allowed' => false, 'reason' => 'concurrent_budget_exceeded'];
            }
            $entries[$requestId] = ['amount_minor' => $amountMinor, 'expires_at' => $now + $ttlSeconds];
            $this->cache->put($stateKey, ['reservations' => $entries], $ttlSeconds + 5);
            return ['allowed' => true, 'reason' => 'reserved'];
        } finally {
            $lock->release();
        }
    }

    public function release(string $scope, string $requestId): void
    {
        if ($scope === '' || $requestId === '') {
            throw new InvalidArgumentException('Invalid reservation key.');
        }
        if (! $this->cache->getStore() instanceof LockProvider) {
            throw new RuntimeException('Distributed locking cache store required.');
        }
        $stateKey = 'ai-budget:state:'.hash('sha256', $scope);
        $lock = $this->cache->lock('ai-budget:lock:'.hash('sha256', $scope), 10);
        if (! $lock->get()) {
            throw new RuntimeException('Could not obtain reservation release lock.');
        }
        try {
            $state = $this->cache->get($stateKey, ['reservations' => []]);
            unset($state['reservations'][$requestId]);
            $this->cache->put($stateKey, $state, 3605);
        } finally {
            $lock->release();
        }
    }
}

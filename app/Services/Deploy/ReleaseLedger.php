<?php

declare(strict_types=1);

namespace App\Services\Deploy;

use RuntimeException;

/** Dedicated private persistent directory, never in a release checkout. */
final class ReleaseLedger
{
    public function __construct(private string $directory) {}

    public function transaction(string $project, callable $operation): mixed
    {
        if (!preg_match('/^[a-z0-9-]{1,80}$/D', $project)) {
            throw new RuntimeException('invalid_project');
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new RuntimeException('ledger_unavailable');
        }
        $path = $this->directory.'/'.$project.'.json';
        $lock = fopen($this->directory.'/'.$project.'.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('ledger_lock_failed');
        }
        try {
            $state = is_file($path) ? json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR) : ['approval' => null, 'audit' => []];
            // Callbacks return a decision; blocked decisions must persist invalidation too.
            $result = $operation($state);
            $temporary = tempnam($this->directory, '.release-');
            if ($temporary === false || file_put_contents($temporary, json_encode($state, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
                throw new RuntimeException('ledger_write_failed');
            }
            chmod($temporary, 0600);
            if (!rename($temporary, $path)) {
                throw new RuntimeException('ledger_commit_failed');
            }
            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function audit(array &$state, string $action, string $actor, string $sha, array $details = []): void
    {
        $previous = $state['audit'] === [] ? '' : $state['audit'][array_key_last($state['audit'])]['hash'];
        $event = ['action' => $action, 'actor' => $actor, 'sha' => $sha, 'at' => time(), 'details' => $details, 'previous_hash' => $previous];
        $event['hash'] = hash('sha256', json_encode($event, JSON_THROW_ON_ERROR));
        $state['audit'][] = $event;
    }
}

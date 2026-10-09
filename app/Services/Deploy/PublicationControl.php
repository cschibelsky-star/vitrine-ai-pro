<?php

declare(strict_types=1);

namespace App\Services\Deploy;

use Closure;
use Throwable;

/** Single server-side boundary shared by Cockpit and machine executors. */
final class PublicationControl
{
    public function __construct(
        private HomologationGate $gate,
        private ReleaseLedger $ledger,
        private Closure $collect,
        private Closure $actorAuthorized,
        private int $approvalTtl = 3600,
    ) {}

    public function invalidate(string $project, string $newSha): void
    {
        $this->ledger->transaction($project, function (array &$state) use ($newSha): void {
            if ($state['approval'] && $state['approval']['sha'] !== $newSha) {
                $state['approval'] = null;
                ReleaseLedger::audit($state, 'approval_invalidated', 'github_push', $newSha);
            }
        });
    }

    public function act(string $project, string $sha, string $action, string $actor, string $executor = ''): array
    {
        return $this->ledger->transaction($project, function (array &$state) use ($project, $sha, $action, $actor, $executor): array {
            try {
                $evidence = ($this->collect)($project, $sha);
                $checks = $evidence['checks'];
            } catch (Throwable) {
                $evidence = ['digest' => null];
                $checks = [];
            }
            $approval = $state['approval'];
            if ($approval && (($checks['target_sha'] ?? '') !== $approval['sha']
                || ($checks['tested_sha'] ?? '') !== $approval['sha']
                || $approval['expires_at'] < time()
                || !($this->actorAuthorized)($approval['actor']))) {
                ReleaseLedger::audit($state, 'approval_invalidated', $actor, $sha);
                $state['approval'] = $approval = null;
            }
            $authorized = ($this->actorAuthorized)($actor);
            $checks['approved_by_authorized_user'] = $action === 'approve' ? $authorized : (bool) $approval;
            $checks['approval_evidence_id'] = $action === 'approve' ? ($evidence['digest'] ?? '') : ($approval['evidence_digest'] ?? '');
            if (($checks['target_sha'] ?? '') !== $sha) {
                $checks['target_sha'] = '';
            }
            $result = $this->gate->evaluate($checks);
            $result['evidence_digest'] = $evidence['digest'] ?? null;
            $result['approval'] = $approval;
            if ($action === 'approve' && !$authorized) {
                $result['allowed'] = false;
                $result['blockers'][] = 'unauthorized_approver';
            }
            if ($action === 'consume' && ($executor === '' || $executor !== ($evidence['executor'] ?? null))) {
                $result['allowed'] = false;
                $result['blockers'][] = 'executor_mismatch';
            }
            if ($result['allowed'] && $action === 'approve') {
                $state['approval'] = ['sha' => $sha, 'actor' => $actor, 'at' => time(), 'expires_at' => time() + $this->approvalTtl, 'evidence_digest' => $evidence['digest']];
                $result['approval'] = $state['approval'];
                ReleaseLedger::audit($state, 'approved', $actor, $sha, ['digest' => $evidence['digest']]);
            } elseif ($result['allowed'] && $action === 'consume') {
                // One use. Serialized with approval/revocation and fresh evidence collection.
                $state['approval'] = null;
                ReleaseLedger::audit($state, 'publication_authorized', $approval['actor'], $sha, ['executor' => $executor, 'digest' => $evidence['digest']]);
            } elseif (in_array($action, ['approve', 'consume'], true)) {
                ReleaseLedger::audit($state, $action.'_blocked', $actor, $sha, ['blockers' => $result['blockers']]);
            }
            return $result;
        });
    }
}

<?php

namespace App\Http\Controllers\Deploy;

use App\Http\Controllers\Controller;
use App\Services\Deploy\PublicationControl;
use Illuminate\Http\Request;

final class PublicationController extends Controller
{
    public function approve(Request $request, PublicationControl $control)
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->is_active === true, 403);
        $data = $request->validate(['project' => 'required|regex:/^[a-z0-9-]{1,80}$/', 'sha' => 'required|regex:/^[a-f0-9]{40}$/']);
        $result = $control->act($data['project'], $data['sha'], 'approve', (string) $request->user()->getAuthIdentifier());
        return response()->json($result, $result['allowed'] ? 200 : 409);
    }

    public function push(Request $request, PublicationControl $control)
    {
        $secret = config('publication.webhook_secret');
        abort_unless(is_string($secret) && strlen($secret) >= 32
            && hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secret), (string) $request->header('X-Hub-Signature-256')), 401);
        abort_unless($request->header('X-GitHub-Event') === 'push', 422);
        foreach (config('homologation_projects.projects', []) as $project) {
            if ($request->input('repository.full_name') === $project['repository'] && $request->input('ref') === 'refs/heads/'.$project['candidate_ref']) {
                $control->invalidate($project['id'], (string) $request->input('after', ''));
            }
        }
        return response()->noContent();
    }

    public function consume(Request $request, PublicationControl $control)
    {
        $token = config('publication.executor_token');
        abort_unless(is_string($token) && strlen($token) >= 32 && hash_equals($token, (string) $request->bearerToken()), 401);
        $data = $request->validate(['project' => 'required|regex:/^[a-z0-9-]{1,80}$/', 'sha' => 'required|regex:/^[a-f0-9]{40}$/', 'executor' => 'required|string|max:80']);
        $result = $control->act($data['project'], $data['sha'], 'consume', 'executor', $data['executor']);
        // Do not expose approval actor or the ledger to machine callers.
        return response()->json(['allowed' => $result['allowed'], 'blockers' => $result['blockers']], $result['allowed'] ? 200 : 409);
    }
}

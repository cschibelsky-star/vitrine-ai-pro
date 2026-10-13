<?php
namespace App\Http\Middleware;
use App\Assessor\TrialPolicy;
use App\Assessor\TrialRequest;
use Closure;
use Illuminate\Http\Request;
final class AssessorTrialAccess
{
    // Must be installed in the cabinet runtime/proxy before access_ready may be attested.
    public function handle(Request $r, Closure $next)
    {
        abort_unless(app()->environment('hml','testing') && config('assessor_trial.enabled')===true,403);
        $u=$r->user();
        abort_unless($u && $u->is_active && $u->hasVerifiedEmail() && $u->company_id,403);
        $t=TrialRequest::where('company_id',$u->company_id)->where('hostname',$r->getHost())->first();
        abort_unless($t && TrialPolicy::accessible($t->status,$t->ends_at,new \DateTimeImmutable('now')),403);
        $response=$next($r);
        $response->headers->set('Cache-Control','private, no-store');
        return $response;
    }
}

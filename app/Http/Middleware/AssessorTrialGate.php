<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
final class AssessorTrialGate
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(app()->environment('hml','testing') && config('assessor_trial.enabled') === true,404);
        // Authenticate after the flag check; Laravel's auth middleware priority otherwise redirects first.
        abort_unless($request->user(),401);
        $response=$next($request);
        $response->headers->set('Cache-Control','private, no-store');
        $response->headers->set('X-Robots-Tag','noindex, nofollow');
        return $response;
    }
}

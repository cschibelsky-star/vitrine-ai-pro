<?php
declare(strict_types=1);

namespace App\Http\Controllers\AssessorOnline;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AssessorAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && $this->hasMembership((int) Auth::id())) {
            return redirect()->route('assessor.dashboard');
        }
        return view('assessor-online.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Credenciais inválidas.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $user = Auth::user();
        if (($user->is_active ?? true) === false || ! $this->hasMembership((int) $user->getAuthIdentifier())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('assessor.login')->withErrors(['email' => 'Acesso ao Assessor Online não autorizado.']);
        }
        return redirect()->route('assessor.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('assessor.login');
    }

    private function hasMembership(int $userId): bool
    {
        return DB::table('assessor_memberships')->where('user_id', $userId)
            ->where('active', true)->whereIn('role', ['admin', 'assessor'])
            ->count() === 1;
    }
}

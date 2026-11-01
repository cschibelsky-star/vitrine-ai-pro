<?php
declare(strict_types=1);

namespace App\Http\Controllers\AssessorOnline;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class AssessorPasswordController extends Controller
{
    public function requestForm(): View
    {
        return view('assessor-online.auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = Str::lower(trim($data['email']));
        $user = User::where('email', $email)->first();

        // Do not reveal whether an email exists or belongs to this product.
        if ($user && $user->is_active && DB::table('assessor_memberships')
            ->where('user_id', $user->id)->where('active', true)
            ->whereIn('role', ['admin', 'assessor'])->exists()) {
            Password::broker('users')->sendResetLink(['email' => $email]);
        }

        return back()->with('status', 'Se o e-mail estiver autorizado, enviaremos as instruções.');
    }

    public function resetForm(string $token, Request $request): View
    {
        return view('assessor-online.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:16', 'confirmed'],
        ]);

        $email = Str::lower(trim($data['email']));
        $user = User::where('email', $email)->first();
        if (! $user || ! $user->is_active || ! DB::table('assessor_memberships')
            ->where('user_id', $user->id)->where('active', true)
            ->whereIn('role', ['admin', 'assessor'])->exists()) {
            return back()->withErrors(['email' => 'Link inválido ou expirado.']);
        }

        $result = Password::broker('users')->reset(
            ['email' => $email, 'password' => $data['password'], 'token' => $data['token']],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $result === Password::PASSWORD_RESET
            ? redirect()->route('assessor.login')->with('status', 'Senha definida. Entre com sua conta.')
            : back()->withErrors(['email' => 'Link inválido ou expirado.']);
    }
}

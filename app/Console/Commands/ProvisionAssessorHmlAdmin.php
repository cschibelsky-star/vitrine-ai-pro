<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;

final class ProvisionAssessorHmlAdmin extends Command
{
    protected $signature = 'assessor:provision-hml-admin {email} {--tenant=gabinete-demonstracao}';
    protected $description = 'Invite an Assessor cabinet administrator in explicitly authorized HML only';

    public function handle(): int
    {
        $url = (string) config('app.url');
        if (config('app.env') !== 'homologation'
            || ! str_ends_with((string) parse_url($url, PHP_URL_HOST), '.hml.vitrineiapro.com.br')
            || env('ASSESSOR_HML_PROVISION_ENABLED') !== 'true') {
            $this->error('Invitations are disabled outside explicitly enabled HML.');
            return self::FAILURE;
        }

        // Refuse to generate a live credential while the actual mail transport is unverified.
        if (config('mail.default') !== 'smtp'
            || ! filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)
            || ! config('mail.mailers.smtp.host')
            || ! config('mail.mailers.smtp.username')
            || ! config('mail.mailers.smtp.password')
            || env('ASSESSOR_HML_MAIL_VERIFIED') !== 'true') {
            $this->error('HML SMTP delivery must be verified before invitations are enabled.');
            return self::FAILURE;
        }

        $email = Str::lower(trim((string) $this->argument('email')));
        $slug = (string) $this->option('tenant');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->error('Invalid email or tenant slug.');
            return self::FAILURE;
        }

        try {
            $user = DB::transaction(function () use ($email, $slug): User {
                if (User::where('email', $email)->exists()) {
                    throw new RuntimeException('Account already exists; no permissions changed.');
                }
                $tenant = DB::table('assessor_tenants')->where('slug', $slug)->first();
                $tenantId = $tenant?->id ?? DB::table('assessor_tenants')->insertGetId([
                    'name' => 'Gabinete Demonstração', 'slug' => $slug,
                    'kind' => 'vereador', 'created_at' => now(), 'updated_at' => now(),
                ]);
                $user = User::create([
                    'name' => 'Administrador do Gabinete', 'email' => $email,
                    'password' => Hash::make(Str::random(80)),
                    'role' => 'assessor', 'is_active' => true,
                ]);
                DB::table('assessor_memberships')->insert([
                    'tenant_id' => $tenantId, 'user_id' => $user->id,
                    'role' => 'admin', 'active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                return $user;
            });

            $token = Password::broker('users')->createToken($user);
            $link = route('assessor.password.reset', ['token' => $token, 'email' => $email]);
            try {
                Mail::raw("Você recebeu um convite para o Assessor Online. Defina sua senha pelo link: {$link}\nSe não solicitou este acesso, ignore a mensagem.", function ($message) use ($email): void {
                    $message->to($email)->subject('Assessor Online - Convite de primeiro acesso');
                });
            } catch (\Throwable $exception) {
                Password::broker('users')->deleteToken($user);
                DB::table('assessor_memberships')->where('user_id', $user->id)->update(['active' => false]);
                $user->forceFill(['is_active' => false])->save();
                throw new RuntimeException('Delivery failed; invitation revoked.', 0, $exception);
            }
        } catch (\Throwable $exception) {
            $this->error('Invitation aborted: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Invitation dispatched through configured SMTP; delivery and activation still require HML verification.');
        return self::SUCCESS;
    }
}

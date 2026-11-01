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
    protected $description = 'Invite a cabinet administrator by email in isolated HML only';

    public function handle(): int
    {
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        if (config('app.env') !== 'homologation'
            || ! str_ends_with($host, '.hml.vitrineiapro.com.br')
            || env('ASSESSOR_HML_PROVISION_ENABLED') !== 'true') {
            $this->error('Provisioning disabled outside explicitly enabled HML.');
            return self::FAILURE;
        }

        // Never create an account when a real delivery channel is unavailable.
        if (config('mail.default') !== 'smtp'
            || ! filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)
            || ! filled(config('mail.mailers.smtp.host'))) {
            $this->error('SMTP is not configured. No account was created.');
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
                    throw new RuntimeException('Existing user: refusing duplicate invitation.');
                }
                $tenant = DB::table('assessor_tenants')->where('slug', $slug)->first();
                $tenantId = $tenant?->id ?? DB::table('assessor_tenants')->insertGetId([
                    'name' => 'Gabinete Demonstração', 'slug' => $slug,
                    'kind' => 'vereador', 'created_at' => now(), 'updated_at' => now(),
                ]);
                $user = User::create([
                    'name' => 'Administrador do Gabinete',
                    'email' => $email,
                    'password' => Hash::make(Str::random(96)),
                    'role' => 'assessor',
                    'is_active' => true,
                ]);
                DB::table('assessor_memberships')->insert([
                    'tenant_id' => $tenantId, 'user_id' => $user->id,
                    'role' => 'admin', 'active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                return $user;
            });

            $token = Password::broker('users')->createToken($user);
            $url = route('assessor.password.reset', ['token' => $token, 'email' => $email]);
            Mail::raw("Ative seu acesso ao Assessor Online definindo sua senha: {$url}\nLink válido por 60 minutos e de uso único.", function ($message) use ($email): void {
                $message->to($email)->subject('Assessor Online - Convite de primeiro acesso');
            });
        } catch (\Throwable $exception) {
            // No sensitive details or activation token in console output.
            $this->error('Invitation could not be completed; check secure operational logs.');
            return self::FAILURE;
        }

        $this->info('HML invitation dispatched. Core administrator privileges were not granted.');
        return self::SUCCESS;
    }
}

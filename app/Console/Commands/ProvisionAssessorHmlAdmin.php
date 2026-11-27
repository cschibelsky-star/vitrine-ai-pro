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
        if (parse_url((string) config('app.url'), PHP_URL_SCHEME) !== 'https'
            || config('app.env') !== 'homologation'
            || ! str_ends_with($host, '.hml.vitrineiapro.com.br')
            || ! filter_var(env('ASSESSOR_HML_PROVISION_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
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
        $allowedEmail = Str::lower(trim((string) env('ASSESSOR_HML_ADMIN_EMAIL', '')));
        if ($allowedEmail === '' || ! hash_equals($allowedEmail, $email)) {
            $this->error('Recipient is not explicitly authorized for HML provisioning.');
            return self::FAILURE;
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->error('Invalid email or tenant slug.');
            return self::FAILURE;
        }

        $expiryMinutes = filter_var(config('auth.passwords.users.expire'), FILTER_VALIDATE_INT);
        if ($expiryMinutes === false || $expiryMinutes < 1) {
            $this->error('Password broker expiration is invalid. No account was created.');
            return self::FAILURE;
        }

        $createdTenantId = null;
        try {
            $user = DB::transaction(function () use ($email, $slug, &$createdTenantId): User {
                if (User::where('email', $email)->exists()) {
                    throw new RuntimeException('Existing user: refusing duplicate invitation.');
                }
                $tenant = DB::table('assessor_tenants')->where('slug', $slug)->first();
                $tenantId = $tenant?->id ?? DB::table('assessor_tenants')->insertGetId([
                    'name' => 'Gabinete Demonstração', 'slug' => $slug,
                    'kind' => 'vereador', 'created_at' => now(), 'updated_at' => now(),
                ]);
                if ($tenant === null) {
                    $createdTenantId = $tenantId;
                }
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
            Mail::raw("Ative seu acesso ao Assessor Online definindo sua senha: {$url}\nLink válido por {$expiryMinutes} minutos e de uso único.", function ($message) use ($email): void {
                $message->to($email)->subject('Assessor Online - Convite de primeiro acesso');
            });
        } catch (\Throwable $exception) {
            // Do not leave an unusable administrative account after delivery failure.
            if (isset($user) && $user instanceof User) {
                DB::transaction(function () use ($user, $createdTenantId): void {
                    DB::table('assessor_memberships')->where('user_id', $user->id)->delete();
                    Password::broker('users')->deleteToken($user);
                    $user->delete();
                    if ($createdTenantId !== null) {
                        // Lock the parent before checking dependent rows. Never delete
                        // an existing cabinet or cascade data added by another operation.
                        $tenant = DB::table('assessor_tenants')->where('id', $createdTenantId)
                            ->lockForUpdate()->first();
                        if ($tenant !== null
                            && ! DB::table('assessor_memberships')->where('tenant_id', $createdTenantId)->exists()
                            && ! DB::table('assessor_citizens')->where('tenant_id', $createdTenantId)->exists()
                            && ! DB::table('assessor_cases')->where('tenant_id', $createdTenantId)->exists()
                            && ! DB::table('assessor_case_events')->where('tenant_id', $createdTenantId)->exists()) {
                            DB::table('assessor_tenants')->where('id', $createdTenantId)->delete();
                        }
                    }
                });
            }
            // No sensitive details or activation token in console output.
            $this->error('Invitation could not be completed; check secure operational logs.');
            return self::FAILURE;
        }

        $this->info('HML invitation dispatched. Core administrator privileges were not granted.');
        return self::SUCCESS;
    }
}

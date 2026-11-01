<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

final class ProvisionAssessorHmlAdmin extends Command
{
    protected $signature = 'assessor:provision-hml-admin {email} {--tenant=gabinete-demonstracao}';
    protected $description = 'Provision an Assessor cabinet administrator in explicitly authorized HML only';

    public function handle(): int
    {
        $url = (string) config('app.url');
        if (config('app.env') !== 'homologation'
            || ! str_ends_with((string) parse_url($url, PHP_URL_HOST), '.hml.vitrineiapro.com.br')
            || env('ASSESSOR_HML_PROVISION_ENABLED') !== 'true') {
            $this->error('Provisioning is disabled outside explicitly enabled HML.');
            return self::FAILURE;
        }

        $email = Str::lower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email.');
            return self::FAILURE;
        }

        $slug = (string) $this->option('tenant');
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->error('Invalid tenant slug.');
            return self::FAILURE;
        }

        $password = $this->secret('Set a new HML-only password (not logged)');
        if (! is_string($password) || strlen($password) < 16) {
            $this->error('Password must contain at least 16 characters.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($email, $slug, $password): void {
                if (User::where('email', $email)->exists()) {
                    throw new RuntimeException('User already exists; refusing to change existing access.');
                }

                $tenant = DB::table('assessor_tenants')->where('slug', $slug)->first();
                $tenantId = $tenant?->id ?? DB::table('assessor_tenants')->insertGetId([
                    'name' => 'Gabinete Demonstração',
                    'slug' => $slug,
                    'kind' => 'vereador',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $user = User::create([
                    'name' => 'Administrador do Gabinete',
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role' => 'assessor',
                    'is_active' => true,
                ]);
                DB::table('assessor_memberships')->insert([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'role' => 'admin',
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            $this->error('Provisioning aborted: '.$exception->getMessage());
            return self::FAILURE;
        } finally {
            unset($password);
        }

        $this->info('HML Assessor account provisioned. No Core administrator role granted.');
        return self::SUCCESS;
    }
}

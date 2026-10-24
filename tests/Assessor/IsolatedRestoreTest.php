<?php
namespace Tests\Assessor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class IsolatedRestoreTest extends TestCase
{
    public function test_real_trial_migration_survives_isolated_file_backup_and_restore(): void
    {
        $directory = sys_get_temp_dir().'/assessor-restore-'.bin2hex(random_bytes(8));
        if (!mkdir($directory, 0700)) {
            $this->fail('Could not create isolated test directory');
        }
        $source = $directory.'/source.sqlite';
        $restored = $directory.'/restored.sqlite';
        touch($source);
        try {
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $source]);
            DB::purge('sqlite');
            Schema::create('users', fn (Blueprint $t) => $t->id());
            Schema::create('companies', fn (Blueprint $t) => $t->id());
            (require __DIR__.'/../../database/migrations/2026_10_13_230000_create_assessor_trial_requests.php')->up();
            DB::table('users')->insert(['id' => 1]);
            DB::table('companies')->insert(['id' => 7]);
            $id = str_repeat('a', 32);
            DB::table('assessor_trial_requests')->insert([
                'id' => $id, 'user_id' => 1, 'company_id' => 7,
                'cabinet_name' => 'Gabinete HML', 'mandate_type' => 'vereador',
                'terms_version' => 'assessor-trial-hml-v1', 'consented_at' => now(),
                'status' => 'requested', 'hostname' => 'isolated.hml.invalid',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('assessor_trial_events')->insert([
                'trial_id' => $id, 'event' => 'requested', 'created_at' => now(),
            ]);
            DB::disconnect('sqlite');
            $this->assertTrue(copy($source, $restored));
            $this->assertSame(hash_file('sha256', $source), hash_file('sha256', $restored));
            config(['database.connections.sqlite.database' => $restored]);
            DB::purge('sqlite');
            $this->assertSame('ok', DB::selectOne('PRAGMA integrity_check')->integrity_check);
            $this->assertSame(1, DB::table('assessor_trial_requests')->count());
            $this->assertSame('requested', DB::table('assessor_trial_requests')->where('id', $id)->value('status'));
            $this->assertSame(1, DB::table('assessor_trial_events')->where('trial_id', $id)->count());
            $this->assertTrue(Schema::hasTable('assessor_trial_requests'));
            $this->assertTrue(Schema::hasTable('assessor_trial_events'));
        } finally {
            DB::disconnect('sqlite');
            config(['database.connections.sqlite.database' => ':memory:']);
            DB::purge('sqlite');
            @unlink($source);
            @unlink($restored);
            @rmdir($directory);
        }
    }
}

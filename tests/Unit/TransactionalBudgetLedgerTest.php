<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Ai\Chat\TransactionalBudgetLedger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Runs entirely on an in-memory test database. Does not call any provider. */
final class TransactionalBudgetLedgerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::connection('sqlite')->create('ai_budget_accounts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('scope')->unique();
            $table->string('currency');
            $table->integer('limit_minor');
            $table->integer('spent_minor')->default(0);
            $table->integer('reserved_minor')->default(0);
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('ai_budget_entries', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('request_id');
            $table->string('state');
            $table->integer('reserved_minor');
            $table->integer('charged_minor')->default(0);
            $table->timestamps();
            $table->unique(['account_id', 'request_id']);
        });
        DB::table('ai_budget_accounts')->insert([
            'scope' => 'project:127', 'currency' => 'BRL', 'limit_minor' => 1000,
            'spent_minor' => 0, 'reserved_minor' => 0,
        ]);
    }

    public function test_reservations_share_a_single_cap(): void
    {
        $ledger = new TransactionalBudgetLedger();
        self::assertTrue($ledger->reserve('project:127', 'BRL', 'first', 600)['allowed']);
        self::assertSame('budget_exceeded', $ledger->reserve('project:127', 'BRL', 'second', 600)['reason']);
        self::assertSame(600, DB::table('ai_budget_accounts')->value('reserved_minor'));
    }

    public function test_settles_verified_cost_and_prevents_repeat_charge(): void
    {
        $ledger = new TransactionalBudgetLedger();
        $ledger->reserve('project:127', 'BRL', 'req', 600);
        self::assertFalse($ledger->settle('project:127', 'BRL', 'req', 200, false)['settled']);
        self::assertTrue($ledger->settle('project:127', 'BRL', 'req', 200, true)['settled']);
        self::assertFalse($ledger->settle('project:127', 'BRL', 'req', 200, true)['settled']);
        self::assertSame(200, DB::table('ai_budget_accounts')->value('spent_minor'));
        self::assertSame(0, DB::table('ai_budget_accounts')->value('reserved_minor'));
    }

    public function test_unverified_cancellation_keeps_reserved_budget(): void
    {
        $ledger = new TransactionalBudgetLedger();
        $ledger->reserve('project:127', 'BRL', 'req', 600);
        self::assertFalse($ledger->cancel('project:127', 'BRL', 'req', false)['released']);
        self::assertSame(600, DB::table('ai_budget_accounts')->value('reserved_minor'));
        self::assertTrue($ledger->cancel('project:127', 'BRL', 'req', true)['released']);
        self::assertSame(0, DB::table('ai_budget_accounts')->value('reserved_minor'));
    }
}

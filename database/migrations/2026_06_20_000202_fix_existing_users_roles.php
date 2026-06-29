<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where(function ($query) {
                    $query->whereNull('role')->orWhere('role', '');
                })
                ->update(['role' => 'admin']);
        }

        if (Schema::hasColumn('users', 'is_active')) {
            DB::table('users')->update(['is_active' => 1]);
        }
    }

    public function down(): void
    {
        //
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('licenses', 'chave')) $table->string('chave')->nullable()->after('vencimento');
            if (! Schema::hasColumn('licenses', 'observacoes')) $table->text('observacoes')->nullable()->after('chave');
        });
    }
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $columns=[];
            if (Schema::hasColumn('licenses','chave')) $columns[]='chave';
            if (Schema::hasColumn('licenses','observacoes')) $columns[]='observacoes';
            if (count($columns)>0) $table->dropColumn($columns);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'dominio_principal')) $table->string('dominio_principal')->nullable()->after('email');
            if (! Schema::hasColumn('companies', 'url_provisoria')) $table->string('url_provisoria')->nullable()->after('dominio_principal');
            if (! Schema::hasColumn('companies', 'url_admin')) $table->string('url_admin')->nullable()->after('url_provisoria');
            if (! Schema::hasColumn('companies', 'ambiente')) $table->enum('ambiente', ['Produção','Homologação','Demo','Desenvolvimento'])->nullable()->after('url_admin');
            if (! Schema::hasColumn('companies', 'status_implantacao')) $table->enum('status_implantacao', ['Não iniciado','Em implantação','Homologação','Publicado','Suspenso'])->default('Não iniciado')->after('ambiente');
        });
    }
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach (['dominio_principal','url_provisoria','url_admin','ambiente','status_implantacao'] as $column) {
                if (Schema::hasColumn('companies', $column)) $table->dropColumn($column);
            }
        });
    }
};

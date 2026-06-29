<?php

declare(strict_types=1);

namespace App\Factory\EnterpriseMaturity\Services;

use Illuminate\Support\Str;

class EnterpriseNameService
{
    protected array $singularMap = [
        'clientes' => 'Cliente',
        'animais' => 'Animal',
        'agendamentos' => 'Agendamento',
        'prontuarios' => 'Prontuario',
        'vacinas' => 'Vacina',
        'financeiro' => 'Financeiro',
        'diagnosticos' => 'Diagnostico',
        'documentos' => 'Documento',
        'planos' => 'Plano',
        'relatorios' => 'Relatorio',
        'categorias' => 'Categoria',
        'bens' => 'Bem',
        'locais' => 'Local',
        'movimentacoes' => 'Movimentacao',
        'registros' => 'Registro',
    ];

    public function modelName(string $slug): string
    {
        return $this->singularMap[$slug] ?? Str::studly(Str::singular($slug));
    }

    public function variableName(string $slug): string
    {
        return Str::camel($this->modelName($slug));
    }

    public function routeName(string $slug): string
    {
        return Str::kebab($slug);
    }

    public function requestName(string $slug): string
    {
        return 'Store' . $this->modelName($slug) . 'Request';
    }

    public function updateRequestName(string $slug): string
    {
        return 'Update' . $this->modelName($slug) . 'Request';
    }
}

<?php

declare(strict_types=1);

namespace App\Factory\Builder\Support;

use Illuminate\Support\Str;

class PortugueseNameHelper
{
    protected array $special = [
        'fornecedores' => 'Fornecedor',
        'categorias' => 'Categoria',
        'contratos' => 'Contrato',
        'documentos' => 'Documento',
        'historicos' => 'Historico',
        'licitações' => 'Licitacao',
        'licitacoes' => 'Licitacao',
        'propostas' => 'Proposta',
        'empenhos' => 'Empenho',
        'pagamentos' => 'Pagamento',
        'clientes' => 'Cliente',
        'usuarios' => 'Usuario',
        'empresas' => 'Empresa',
        'produtos' => 'Produto',
        'planos' => 'Plano',
        'cidades' => 'Cidade',
        'eventos' => 'Evento',
        'atrativos' => 'Atrativo',
        'pacientes' => 'Paciente',
        'unidades' => 'Unidade',
        'atendimentos' => 'Atendimento',
        'bens' => 'Bem',
        'locais' => 'Local',
        'movimentacoes' => 'Movimentacao',
        'leads' => 'Lead',
    ];

    public function slug(string $name): string
    {
        return Str::slug($name, '_');
    }

    public function tableName(string $name): string
    {
        return Str::plural($this->slug($name));
    }

    public function modelName(string $name): string
    {
        $slug = $this->slug($name);

        if (isset($this->special[$slug])) {
            return $this->special[$slug];
        }

        if (str_ends_with($slug, 'coes')) {
            return Str::studly(substr($slug, 0, -4) . 'cao');
        }

        if (str_ends_with($slug, 'oes')) {
            return Str::studly(substr($slug, 0, -3) . 'ao');
        }

        if (str_ends_with($slug, 'ores')) {
            return Str::studly(substr($slug, 0, -4) . 'or');
        }

        if (str_ends_with($slug, 'ais')) {
            return Str::studly(substr($slug, 0, -3) . 'al');
        }

        if (str_ends_with($slug, 'is')) {
            return Str::studly(substr($slug, 0, -2) . 'il');
        }

        if (str_ends_with($slug, 'es')) {
            return Str::studly(substr($slug, 0, -2));
        }

        if (str_ends_with($slug, 's')) {
            return Str::studly(substr($slug, 0, -1));
        }

        return Str::studly($slug);
    }

    public function listPageName(string $name): string
    {
        $slug = $this->slug($name);

        return match ($slug) {
            'fornecedores' => 'ListFornecedores',
            'categorias' => 'ListCategorias',
            'contratos' => 'ListContratos',
            'documentos' => 'ListDocumentos',
            'historicos' => 'ListHistoricos',
            'licitacoes' => 'ListLicitacoes',
            'propostas' => 'ListPropostas',
            'clientes' => 'ListClientes',
            'empresas' => 'ListEmpresas',
            'produtos' => 'ListProdutos',
            default => 'List' . Str::studly(Str::plural($slug)),
        };
    }

    public function title(string $name): string
    {
        return Str::headline(str_replace('_', ' ', $this->slug($name)));
    }
}

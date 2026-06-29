<?php

declare(strict_types=1);

namespace App\Factory\Architecture\Services;

use Illuminate\Support\Str;

class DomainKnowledgeEngine
{
    public function analyze(string $input): array
    {
        $text = Str::of($input)->lower()->ascii()->toString();
        $domain = $this->detectDomain($text);

        return [
            'input' => $input,
            'domain' => $domain,
            'label' => $this->label($domain),
            'modules' => $this->modules($domain),
            'typical_relationships' => $this->relationships($domain),
            'recommended_dashboards' => $this->dashboards($domain),
            'detected_at' => now()->toISOString(),
        ];
    }

    public function detectDomain(string $text): string
    {
        if (str_contains($text, 'licitacao') || str_contains($text, 'compras') || str_contains($text, 'contrato')) {
            return 'compras_publicas';
        }

        if (str_contains($text, 'turismo') || str_contains($text, 'guia') || str_contains($text, 'cidade') || str_contains($text, 'evento')) {
            return 'turismo_guia_digital';
        }

        if (str_contains($text, 'saude') || str_contains($text, 'paciente') || str_contains($text, 'sismed')) {
            return 'saude_sismed';
        }

        if (str_contains($text, 'portal') || str_contains($text, 'noticia') || str_contains($text, 'rss')) {
            return 'portal_news';
        }

        if (str_contains($text, 'tv') || str_contains($text, 'video') || str_contains($text, 'ao vivo')) {
            return 'tv_digital';
        }

        if (str_contains($text, 'crm') || str_contains($text, 'lead') || str_contains($text, 'comercial')) {
            return 'crm_comercial';
        }

        if (str_contains($text, 'patrimonio') || str_contains($text, 'bens') || str_contains($text, 'inventario')) {
            return 'patrimonio';
        }

        return 'generico';
    }

    protected function label(string $domain): string
    {
        return match ($domain) {
            'compras_publicas' => 'Compras Públicas',
            'turismo_guia_digital' => 'Turismo / Guia Digital da Cidade',
            'saude_sismed' => 'Saúde / SISMED',
            'portal_news' => 'Portal de Notícias',
            'tv_digital' => 'TV Digital Enterprise',
            'crm_comercial' => 'CRM Comercial',
            'patrimonio' => 'Gestão de Patrimônio',
            default => 'Sistema Genérico',
        };
    }

    protected function modules(string $domain): array
    {
        return match ($domain) {
            'compras_publicas' => [
                'categorias', 'fornecedores', 'licitacoes', 'propostas', 'contratos',
                'empenhos', 'pagamentos', 'documentos', 'fiscalizacao', 'historicos',
            ],
            'turismo_guia_digital' => [
                'cidades', 'atrativos', 'eventos', 'categorias', 'roteiros',
                'galerias', 'comercios', 'hospedagens', 'gastronomia', 'agenda',
            ],
            'saude_sismed' => [
                'pacientes', 'unidades', 'profissionais', 'agendamentos',
                'atendimentos', 'documentos', 'historicos', 'especialidades',
            ],
            'portal_news' => [
                'materias', 'categorias', 'cidades', 'fontes_rss', 'autores',
                'videos', 'banners', 'revisoes', 'tags',
            ],
            'tv_digital' => [
                'programas', 'videos', 'ao_vivo', 'playlists', 'reporteres_ia',
                'banners', 'grade', 'materias', 'patrocinadores',
            ],
            'crm_comercial' => [
                'clientes', 'leads', 'propostas', 'followups', 'produtos',
                'planos', 'contratos', 'financeiro',
            ],
            'patrimonio' => [
                'bens', 'categorias', 'locais', 'movimentacoes', 'baixas',
                'manutencoes', 'documentos', 'inventarios',
            ],
            default => ['registros', 'categorias', 'documentos', 'historicos'],
        };
    }

    protected function relationships(string $domain): array
    {
        return match ($domain) {
            'compras_publicas' => [
                'fornecedor belongsTo categoria',
                'proposta belongsTo licitacao',
                'proposta belongsTo fornecedor',
                'contrato belongsTo fornecedor',
                'contrato belongsTo licitacao',
                'documento belongsTo licitacao',
                'empenho belongsTo contrato',
                'pagamento belongsTo empenho',
            ],
            'turismo_guia_digital' => [
                'atrativo belongsTo cidade',
                'evento belongsTo cidade',
                'roteiro belongsTo cidade',
                'comercio belongsTo cidade',
                'galeria belongsTo atrativo',
            ],
            'saude_sismed' => [
                'atendimento belongsTo paciente',
                'atendimento belongsTo unidade',
                'profissional belongsTo especialidade',
                'agendamento belongsTo paciente',
            ],
            'portal_news' => [
                'materia belongsTo categoria',
                'materia belongsTo cidade',
                'materia belongsTo autor',
                'video belongsTo materia',
            ],
            'tv_digital' => [
                'video belongsTo programa',
                'playlist hasMany videos',
                'materia hasOne video',
                'patrocinador hasMany banners',
            ],
            default => [
                'documento belongsTo registro',
                'historico belongsTo registro',
            ],
        };
    }

    protected function dashboards(string $domain): array
    {
        return match ($domain) {
            'compras_publicas' => ['licitações abertas', 'contratos ativos', 'valor contratado', 'documentos pendentes', 'vencimentos próximos'],
            'turismo_guia_digital' => ['atrativos ativos', 'eventos próximos', 'cidades atendidas', 'roteiros publicados'],
            'saude_sismed' => ['pacientes ativos', 'atendimentos do dia', 'agendamentos', 'unidades ativas'],
            'portal_news' => ['matérias publicadas', 'RSS capturados', 'vídeos gerados', 'mais lidas'],
            'tv_digital' => ['vídeos publicados', 'ao vivo', 'programas ativos', 'patrocinadores'],
            default => ['total de registros', 'ativos', 'pendentes', 'últimos registros'],
        };
    }
}

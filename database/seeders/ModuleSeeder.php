<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\Plan;
use App\Models\PlanModule;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            'TV Digital Enterprise' => [
                ['Portal de Notícias', 'tv-portal-noticias', 'TV Digital', 'incluido', 0, 'Ativo'],
                ['RSS / Captura Automática', 'tv-rss-captura', 'TV Digital', 'incluido', 0, 'Ativo'],
                ['Radar Regional', 'tv-radar-regional', 'TV Digital', 'premium', 0, 'Ativo'],
                ['Aprovação Editorial', 'tv-aprovacao-editorial', 'TV Digital', 'incluido', 0, 'Ativo'],
                ['IA Editorial / Gemini', 'tv-ia-editorial', 'TV Digital', 'premium', 0, 'Ativo'],
                ['Vídeos', 'tv-videos', 'TV Digital', 'incluido', 0, 'Ativo'],
                ['TV Play', 'tv-play', 'TV Digital', 'premium', 0, 'Ativo'],
                ['Ao Vivo', 'tv-ao-vivo', 'TV Digital', 'premium', 0, 'Ativo'],
                ['Repórter IA / HeyGen', 'tv-reporter-ia', 'TV Digital', 'premium', 497, 'Ativo'],
                ['Guia Comercial', 'tv-guia-comercial', 'TV Digital', 'incluido', 0, 'Ativo'],
                ['Banners e Monetização', 'tv-banners-monetizacao', 'TV Digital', 'extra', 197, 'Ativo'],
                ['Empregos & Negócios', 'tv-empregos-negocios', 'TV Digital', 'extra', 197, 'Ativo'],
                ['Newsletter', 'tv-newsletter', 'TV Digital', 'extra', 97, 'Ativo'],
                ['Notificações Push', 'tv-push', 'TV Digital', 'extra', 97, 'Ativo'],
                ['PWA / App Android', 'tv-pwa-app', 'TV Digital', 'premium', 0, 'Ativo'],
                ['Dashboard Editorial', 'tv-dashboard-editorial', 'TV Digital', 'premium', 0, 'Ativo'],
            ],
            'Portal News AI' => [
                ['Portal de Notícias', 'news-portal-noticias', 'News AI', 'incluido', 0, 'Ativo'],
                ['RSS / Captura Automática', 'news-rss-captura', 'News AI', 'incluido', 0, 'Ativo'],
                ['IA Editorial / Gemini', 'news-ia-editorial', 'News AI', 'premium', 0, 'Ativo'],
                ['Categorias e Editorias', 'news-categorias-editorias', 'News AI', 'incluido', 0, 'Ativo'],
                ['Banners e Monetização', 'news-banners-monetizacao', 'News AI', 'extra', 197, 'Ativo'],
                ['Newsletter', 'news-newsletter', 'News AI', 'extra', 97, 'Ativo'],
            ],
            'Visite Cidade' => [
                ['Atrativos Turísticos', 'guia-atrativos', 'Guia Digital', 'incluido', 0, 'Ativo'],
                ['Eventos', 'guia-eventos', 'Guia Digital', 'incluido', 0, 'Ativo'],
                ['Gastronomia', 'guia-gastronomia', 'Guia Digital', 'incluido', 0, 'Ativo'],
                ['Hospedagem', 'guia-hospedagem', 'Guia Digital', 'incluido', 0, 'Ativo'],
                ['Comércio Local', 'guia-comercio-local', 'Guia Digital', 'extra', 197, 'Ativo'],
                ['Roteiros Recomendados', 'guia-roteiros', 'Guia Digital', 'extra', 197, 'Ativo'],
                ['Mapa / Localização', 'guia-mapa', 'Guia Digital', 'incluido', 0, 'Ativo'],
                ['Formulário de Cadastro', 'guia-formulario-cadastro', 'Guia Digital', 'incluido', 0, 'Ativo'],
                ['Painel Administrativo', 'guia-painel-admin', 'Guia Digital', 'premium', 0, 'Ativo'],
                ['PWA Mobile', 'guia-pwa-mobile', 'Guia Digital', 'premium', 0, 'Ativo'],
                ['Landing Page de Captação', 'guia-landing-captacao', 'Guia Digital', 'incluido', 0, 'Ativo'],
                ['Área do Operador', 'guia-area-operador', 'Guia Digital', 'premium', 0, 'Ativo'],
                ['Categorias Personalizadas', 'guia-categorias-personalizadas', 'Guia Digital', 'extra', 197, 'Ativo'],
                ['Galeria de Imagens', 'guia-galeria-imagens', 'Guia Digital', 'incluido', 0, 'Ativo'],
            ],
            'Município Digital IA' => [
                ['Portal Institucional', 'gov-portal-institucional', 'Governo Digital', 'incluido', 0, 'Ativo'],
                ['Páginas Oficiais', 'gov-paginas-oficiais', 'Governo Digital', 'incluido', 0, 'Ativo'],
                ['Notícias Institucionais', 'gov-noticias', 'Governo Digital', 'incluido', 0, 'Ativo'],
                ['Solicitações / Atendimento', 'gov-solicitacoes-atendimento', 'Governo Digital', 'premium', 0, 'Ativo'],
                ['Diagnóstico Digital', 'gov-diagnostico-digital', 'Governo Digital', 'incluido', 0, 'Ativo'],
                ['Documentos Públicos', 'gov-documentos-publicos', 'Governo Digital', 'incluido', 0, 'Ativo'],
                ['Agenda Institucional', 'gov-agenda', 'Governo Digital', 'extra', 197, 'Ativo'],
                ['IA de Atendimento', 'gov-ia-atendimento', 'Governo Digital', 'premium', 497, 'Ativo'],
                ['Relatórios de Atendimento', 'gov-relatorios-atendimento', 'Governo Digital', 'premium', 0, 'Ativo'],
                ['Landing Institucional', 'gov-landing-institucional', 'Governo Digital', 'incluido', 0, 'Ativo'],
            ],
            'SISMED' => [
                ['Cadastro de Pacientes', 'sismed-pacientes', 'SISMED', 'futuro', 0, 'Futuro'],
                ['Cadastro de Profissionais', 'sismed-profissionais', 'SISMED', 'futuro', 0, 'Futuro'],
                ['Agenda de Atendimentos', 'sismed-agenda', 'SISMED', 'futuro', 0, 'Futuro'],
                ['Triagem', 'sismed-triagem', 'SISMED', 'futuro', 0, 'Futuro'],
                ['Encaminhamentos', 'sismed-encaminhamentos', 'SISMED', 'futuro', 0, 'Futuro'],
                ['Relatórios de Saúde', 'sismed-relatorios', 'SISMED', 'futuro', 0, 'Futuro'],
                ['Painel da Secretaria', 'sismed-painel-secretaria', 'SISMED', 'futuro', 0, 'Futuro'],
                ['IA de Apoio Administrativo', 'sismed-ia-admin', 'SISMED', 'futuro', 0, 'Futuro'],
            ],
        ];

        $ordem = 1;
        foreach ($catalogo as $produtoNome => $modulos) {
            $produto = Product::firstOrCreate(['nome' => $produtoNome], ['categoria' => 'SaaS', 'status' => 'Ativo']);
            foreach ($modulos as [$nome, $codigo, $categoria, $tipo, $valor, $status]) {
                Module::updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'product_id' => $produto->id,
                        'nome' => $nome,
                        'descricao' => 'Módulo operacional do ecossistema Vitrine AI Pro.',
                        'categoria' => $categoria,
                        'tipo' => $tipo,
                        'valor_adicional' => $valor,
                        'status' => $status,
                        'ordem' => $ordem++,
                    ]
                );
            }
        }

        $this->vincularModulosAosPlanos();
        $this->criarInstanciasOficiais();
    }

    private function vincularModulosAosPlanos(): void
    {
        $regras = [
            'Start' => ['incluido' => ['portal', 'noticias', 'categorias', 'atrativos', 'eventos', 'gastronomia', 'hospedagem', 'mapa', 'formulario', 'landing']],
            'Pro' => ['incluido' => ['portal', 'noticias', 'rss', 'captura', 'ia-editorial', 'banners', 'videos', 'newsletter', 'comercio', 'roteiros', 'painel-admin', 'pwa']],
            'Enterprise' => ['incluido' => ['portal', 'noticias', 'rss', 'captura', 'radar', 'ia-editorial', 'videos', 'tv-play', 'ao-vivo', 'reporter-ia', 'dashboard', 'area-operador', 'ia-atendimento', 'relatorios']],
            'Governo' => ['incluido' => ['portal', 'paginas', 'noticias', 'diagnostico', 'documentos', 'solicitacoes', 'ia-atendimento', 'relatorios', 'landing']],
            'White Label' => ['incluido' => ['portal', 'noticias', 'rss', 'captura', 'radar', 'ia-editorial', 'videos', 'tv-play', 'ao-vivo', 'dashboard', 'pwa', 'landing']],
        ];

        foreach (Plan::with('product')->get() as $plano) {
            $nomePlano = $plano->nome;
            $keywords = $regras[$nomePlano]['incluido'] ?? $regras['Start']['incluido'];
            $modulos = Module::where('product_id', $plano->product_id)->get();

            foreach ($modulos as $modulo) {
                $incluido = collect($keywords)->contains(fn ($kw) => Str::contains($modulo->codigo, $kw));
                PlanModule::updateOrCreate(
                    ['plan_id' => $plano->id, 'module_id' => $modulo->id],
                    [
                        'tipo_inclusao' => $incluido ? 'incluido' : ($modulo->tipo === 'premium' ? 'premium' : ($modulo->tipo === 'extra' ? 'extra' : 'bloqueado')),
                        'valor_adicional' => $incluido ? 0 : $modulo->valor_adicional,
                        'limite_uso' => $modulo->codigo === 'tv-reporter-ia' ? 'Franquia conforme contrato' : null,
                        'status' => $modulo->status === 'Futuro' ? 'Inativo' : 'Ativo',
                        'observacoes' => null,
                    ]
                );
            }
        }
    }

    private function criarInstanciasOficiais(): void
    {
        Company::updateOrCreate(
            ['nome' => 'Conheça Sumaré'],
            [
                'responsavel' => 'Vitrine AI Pro',
                'cidade' => 'Sumaré',
                'estado' => 'SP',
                'produto_principal' => 'Conheça Sua Cidade',
                'dominio_principal' => 'conhecasumare.com.br',
                'dominio_landing' => 'conhecasumare.com.br',
                'ambiente' => 'Produção',
                'tipo_instancia' => 'piloto',
                'status_implantacao' => 'Publicado',
                'status' => 'Ativo',
            ]
        );

        Company::updateOrCreate(
            ['nome' => 'Conheça Sua Cidade'],
            [
                'responsavel' => 'Vitrine AI Pro',
                'produto_principal' => 'Conheça Sua Cidade',
                'dominio_principal' => 'conhecasuacidade.com.br',
                'dominio_landing' => 'conhecasuacidade.com.br',
                'ambiente' => 'Produção',
                'tipo_instancia' => 'demo',
                'status_implantacao' => 'Publicado',
                'status' => 'Ativo',
            ]
        );
    }
}

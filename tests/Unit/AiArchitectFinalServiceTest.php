<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Factory\Finalization\Services\AiArchitectFinalService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AiArchitectFinalServiceTest extends TestCase
{
    /**
     * @dataProvider productDomainProvider
     */
    public function test_it_selects_the_canonical_product_domain(
        string $request,
        string $expectedDomain,
        array $expectedModules,
    ): void {
        $result = app(AiArchitectFinalService::class)->architect($request);

        $this->assertSame($expectedDomain, $result['domain']);

        $moduleSlugs = array_column($result['blueprint']['modules'], 'slug');

        foreach ($expectedModules as $module) {
            $this->assertContains($module, $moduleSlugs);
        }

        File::delete($result['blueprint_path']);
        File::delete($result['architecture_path']);
    }

    public static function productDomainProvider(): array
    {
        return [
            'guia digital' => [
                'Crie um Guia Digital da Cidade com atrativos, eventos, empresas e leads',
                'guia_digital',
                ['cidades', 'atrativos', 'eventos', 'empresas', 'leads'],
            ],
            'tv digital' => [
                'Crie uma TV Digital Enterprise com notícias, vídeos, RSS e anúncios',
                'tv_digital',
                ['noticias', 'videos', 'fontes_rss', 'anuncios'],
            ],
            'assessorgov' => [
                'Crie o AssessorGov IA com oportunidades públicas, análises e alertas',
                'assessorgov',
                ['clientes', 'oportunidades', 'analises', 'alertas'],
            ],
            'govtech compras' => [
                'Crie um GovTech Compras IA para ETP, DFD e termo de referência',
                'govtech_compras',
                ['processos', 'documentos', 'fornecedores', 'revisoes_ia'],
            ],
        ];
    }
}

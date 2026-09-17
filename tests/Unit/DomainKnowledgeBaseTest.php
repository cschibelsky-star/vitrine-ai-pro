<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Factory\AI\Services\DomainKnowledgeBase;
use App\Factory\EnterpriseMaturity\Services\EnterpriseNameService;
use App\Factory\RealBuilder\Services\RealBuilderNameService;
use PHPUnit\Framework\TestCase;

class DomainKnowledgeBaseTest extends TestCase
{
    public function test_captação_de_recursos_tem_prioridade_sobre_documentos(): void
    {
        $knowledgeBase = new DomainKnowledgeBase();

        $domain = $knowledgeBase->match(
            'somos uma organizacao de inclusao e queremos identificar oportunidades de financiamento compativeis avaliar aderencia identificar documentos e lacunas e gerar plano de acao para candidatura'
        );

        $this->assertSame('captacao_recursos', $domain);
    }

    public function test_documento_isolado_continua_no_dominio_fornecedores(): void
    {
        $knowledgeBase = new DomainKnowledgeBase();

        $domain = $knowledgeBase->match('controle de fornecedores contratos e documentos');

        $this->assertSame('fornecedores', $domain);
    }

    public function test_escola_de_musica_e_artes_gera_dominio_e_modulos_especificos(): void
    {
        $knowledgeBase = new DomainKnowledgeBase();

        $prompt = 'criar sistema de cadastro para escola de musica e artes com alunos cursos turmas professores e matriculas';
        $domain = $knowledgeBase->match($prompt);
        $blueprint = $knowledgeBase->blueprintFor($domain, $prompt);

        $this->assertSame('escola_musica_artes', $domain);
        $this->assertSame('gestao_escola_musica_artes', $blueprint['slug']);
        $this->assertSame(
            ['alunos', 'responsaveis', 'professores', 'cursos', 'turmas', 'matriculas', 'atendimentos'],
            array_column($blueprint['modules'], 'slug')
        );
    }

    public function test_nomes_de_modelos_da_escola_nao_usam_singularizacao_inglesa(): void
    {
        $services = [
            new RealBuilderNameService(),
            new EnterpriseNameService(),
        ];

        foreach ($services as $names) {
            $this->assertSame('Aluno', $names->modelName('alunos'));
            $this->assertSame('Responsavel', $names->modelName('responsaveis'));
            $this->assertSame('Professor', $names->modelName('professores'));
            $this->assertSame('Curso', $names->modelName('cursos'));
            $this->assertSame('Turma', $names->modelName('turmas'));
            $this->assertSame('Matricula', $names->modelName('matriculas'));
            $this->assertSame('Atendimento', $names->modelName('atendimentos'));
        }
    }
}

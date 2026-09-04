<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Factory\AI\Services\DomainKnowledgeBase;
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
}

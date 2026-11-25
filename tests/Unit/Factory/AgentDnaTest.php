<?php

declare(strict_types=1);

namespace Tests\Unit\Factory;

use App\Factory\Domain\Agents\AgentDna;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AgentDnaTest extends TestCase
{
    private function input(): array
    {
        return ['name'=>'Analista', 'domain'=>'DEV', 'department'=>'QA',
            'objective'=>'Revisar', 'instructions'=>'Produzir parecer', 'provider'=>'roteia',
            'model'=>'configured-model', 'skills'=>['qa.v1']];
    }

    public function test_only_draft_and_no_permissions(): void
    {
        foreach (AgentDna::PROVIDERS as $provider) {
            $dna = AgentDna::draft(array_replace($this->input(), ['provider'=>$provider]));
            self::assertSame('draft', $dna['status']);
            self::assertSame([], $dna['granted_tools']);
            self::assertSame(0, $dna['budget_cents']);
            self::assertNull($dna['via_approval']);
        }
    }

    public function test_avello_not_allowed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AgentDna::draft(array_replace($this->input(), ['provider'=>'avello']));
    }

    public function test_eco_requires_company(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AgentDna::draft(array_replace($this->input(), ['domain'=>'ECO']));
    }

    public function test_dev_rejects_company(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AgentDna::draft($this->input() + ['company_id'=>1]);
    }

    public function test_cannot_grant_approval_or_tools_in_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AgentDna::draft($this->input() + ['via_approval'=>true]);
    }
}

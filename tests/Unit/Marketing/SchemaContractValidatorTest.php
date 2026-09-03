<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Application\SchemaContractValidator;
use PHPUnit\Framework\TestCase;

class SchemaContractValidatorTest extends PHPUnit\Framework\TestCase
{
    public function test_empty_decoded_json_object_is_accepted_as_object(): void
    {
        $payload = [
            'strategy_id' => 'STRATEGY-001',
            'campaign_id' => 'CAM-001',
            'product_readiness' => [],
            'icp' => [],
            'pain_points' => [],
            'value_proposition' => 'Proposta validada.',
            'positioning' => 'Posicionamento validado.',
            'differentiators' => [],
            'core_message' => 'Mensagem validada.',
            'status' => 'completed',
        ];

        $validator = new SchemaContractValidator();
        $validator->assertValid('strategy-output', $payload);

        $this->assertTrue(true);
    }
}

<?php
declare(strict_types=1);

namespace Tests\Unit\AssessorOnline;

use App\AssessorOnline\Audesp\SubmissionPreflight;
use PHPUnit\Framework\TestCase;

final class SubmissionPreflightTest extends TestCase
{
    /** @return array<string,mixed> */
    private function base(): array
    {
        return [
            'jurisdiction' => 'municipal',
            'origin_estimated_ufesp' => 2500,
            'origin_started_at' => '2026-11-03',
            'stage' => 'edital',
            'origin_already_reported_in_legacy_engineering' => false,
            'state_contracts_gov_integrated' => false,
        ];
    }

    public function test_exact_threshold_on_cutover_date_is_eligible_for_preflight_only(): void
    {
        $result = (new SubmissionPreflight())->evaluate($this->base());
        self::assertTrue($result['eligible']);
        self::assertSame('preflight_only', $result['reason']);
    }

    public function test_missing_data_fails_closed(): void
    {
        $data = $this->base();
        unset($data['origin_estimated_ufesp']);
        self::assertSame('missing_origin_estimated_ufesp', (new SubmissionPreflight())->evaluate($data)['reason']);
    }

    public function test_below_threshold_is_out_of_scope(): void
    {
        $data = $this->base();
        $data['origin_estimated_ufesp'] = 2499.99;
        self::assertSame('below_origin_threshold', (new SubmissionPreflight())->evaluate($data)['reason']);
    }

    public function test_legacy_engineering_is_not_migrated(): void
    {
        $data = $this->base();
        $data['origin_already_reported_in_legacy_engineering'] = true;
        self::assertFalse((new SubmissionPreflight())->evaluate($data)['eligible']);
    }

    public function test_state_integrated_contracts_gov_must_not_duplicate(): void
    {
        $data = $this->base();
        $data['jurisdiction'] = 'state';
        $data['state_contracts_gov_integrated'] = true;
        self::assertSame('verify_automatic_contracts_gov_route', (new SubmissionPreflight())->evaluate($data)['reason']);
    }

    public function test_previous_origin_requires_stock_review(): void
    {
        $data = $this->base();
        $data['origin_started_at'] = '2026-11-02';
        self::assertSame('requires_retrospective_stock_review', (new SubmissionPreflight())->evaluate($data)['reason']);
    }

    public function test_ata_requires_confirmed_origin_transmission(): void
    {
        $data = $this->base();
        $data['stage'] = 'ata';
        self::assertTrue((new SubmissionPreflight())->evaluate($data)['requires_origin']);
        $data['origin_transmitted'] = true;
        self::assertTrue((new SubmissionPreflight())->evaluate($data)['eligible']);
    }

    public function test_invalid_date_is_not_accepted(): void
    {
        $data = $this->base();
        $data['origin_started_at'] = '2026-11-99';
        self::assertSame('invalid_input', (new SubmissionPreflight())->evaluate($data)['reason']);
    }
}

<?php

namespace Tests\Unit;

use App\Models\License;
use App\Services\Licensing\TrialLicenseService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class TrialLicenseServiceTest extends TestCase
{
    public function test_valid_trial_allows_access_before_expiration(): void
    {
        $license = new License([
            'status' => 'Trial',
            'inicio' => '2026-10-13',
            'vencimento' => '2026-11-12',
        ]);

        $service = new TrialLicenseService();

        $this->assertFalse($service->isTrialAccessAllowed($license, CarbonImmutable::parse('2026-10-12')));
        $this->assertTrue($service->isTrialAccessAllowed($license, CarbonImmutable::parse('2026-10-13')));
        $this->assertTrue($service->isTrialAccessAllowed($license, CarbonImmutable::parse('2026-11-11')));
        $this->assertFalse($service->isTrialAccessAllowed($license, CarbonImmutable::parse('2026-11-12')));
    }

    public function test_missing_dates_and_non_trial_status_fail_closed(): void
    {
        $service = new TrialLicenseService();
        $this->assertFalse($service->isTrialAccessAllowed(new License(['status' => 'Trial']), CarbonImmutable::today()));
        $this->assertFalse($service->isTrialAccessAllowed(new License([
            'status' => 'Suspensa',
            'inicio' => '2026-10-13',
            'vencimento' => '2026-11-12',
        ]), CarbonImmutable::parse('2026-10-20')));
    }
}

<?php

namespace Tests\Unit\Services\Deploy;

use App\Services\Deploy\HomologationGate;
use PHPUnit\Framework\TestCase;

final class HomologationGateTest extends TestCase
{
    private function approved(): array
    {
        $sha = str_repeat('a', 40);
        return [
            'hml_url'=>'https://hml.example.com',
            'production_url'=>'https://example.com',
            'hml_dns_ok'=>true,
            'hml_tls_ok'=>true,
            'hml_health_ok'=>true,
            'isolated_runtime'=>true,
            'isolated_data'=>true,
            'ci_green'=>true,
            'tests_passed'=>true,
            'backup_verified'=>true,
            'rollback_ready'=>true,
            'approved_by_authorized_user'=>true,
            'approval_evidence_id'=>'approval-123',
            'tested_sha'=>$sha,
            'target_sha'=>$sha,
        ];
    }

    public function test_all_checks_are_required(): void
    {
        $result=(new HomologationGate())->evaluate([]);
        self::assertFalse($result['allowed']);
        self::assertContains('not_verified:hml_dns_ok', $result['blockers']);
    }

    public function test_requires_distinct_urls(): void
    {
        $release=$this->approved();
        $release['hml_url']=$release['production_url'];
        $result=(new HomologationGate())->evaluate($release);
        self::assertFalse($result['allowed']);
        self::assertContains('same_url',$result['blockers']);
    }

    public function test_disallows_new_sha_after_approval(): void
    {
        $release=$this->approved();
        $release['target_sha']=str_repeat('b',40);
        $result=(new HomologationGate())->evaluate($release);
        self::assertFalse($result['allowed']);
        self::assertContains('sha_not_approved',$result['blockers']);
    }

    public function test_disallows_unverified_isolation(): void
    {
        $release=$this->approved();
        $release['isolated_runtime']=false;
        self::assertFalse((new HomologationGate())->evaluate($release)['allowed']);
    }

    public function test_approved_release_passes_gate(): void
    {
        self::assertTrue((new HomologationGate())->evaluate($this->approved())['allowed']);
    }
}

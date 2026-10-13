<?php
namespace App\Assessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use DomainException;
final class TrialService
{
    public function __construct(private HmlTrialProvider $provider) {}
    public function gate(): void
    {
        if (!app()->environment('hml','testing') || config('assessor_trial.enabled') !== true) {
            throw new DomainException('trial_hml_disabled');
        }
    }
    private function event(TrialRequest $t, string $event): void
    {
        DB::table('assessor_trial_events')->insert(['trial_id'=>$t->id,'event'=>$event,'created_at'=>Date::now('UTC')]);
    }
    public function request(int $userId, int $companyId, string $name, string $type): TrialRequest
    {
        $this->gate();
        return DB::transaction(function () use ($userId,$companyId,$name,$type) {
            // Serialize duplicate requests on an existing canonical company row.
            DB::table('companies')->where('id',$companyId)->lockForUpdate()->firstOrFail();
            if (TrialRequest::where('company_id',$companyId)->exists()) { throw new DomainException('trial_already_requested'); }
            $id = bin2hex(random_bytes(16));
            $t = new TrialRequest;
            $t->forceFill(['id'=>$id,'user_id'=>$userId,'company_id'=>$companyId,'cabinet_name'=>$name,
                'mandate_type'=>$type,'terms_version'=>config('assessor_trial.terms_version'),
                'consented_at'=>Date::now('UTC'),'status'=>'requested','hostname'=>TrialPolicy::host($id)])->save();
            $this->event($t,'requested');
            return $t;
        });
    }
    public function provision(string $id): TrialRequest
    {
        $this->gate();
        // HML-only, serialized integration contract. Adapter timeout must be bounded.
        return DB::transaction(function () use ($id) {
            $t=TrialRequest::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($t->status === 'active') { return $t; }
            if (!in_array($t->status,['requested','blocked'],true)) { throw new DomainException('invalid_trial_state'); }
            try {
                $receipt=$this->provider->provision($t->id,(int)$t->company_id,$t->hostname);
                TrialPolicy::receipt($receipt,$t->id,(int)$t->company_id);
            } catch (\Throwable $e) {
                // Never persist provider messages that may contain secrets or personal data.
                $t->forceFill(['status'=>'blocked','blocker'=>'official_provider_or_gate_unavailable'])->save();
                $this->event($t,'provision_blocked');
                return $t;
            }
            [$start,$end]=TrialPolicy::dates(new \DateTimeImmutable('now'));
            $t->forceFill(['status'=>'active','route_id'=>$receipt['route_id'],'starts_at'=>$start,
                'ends_at'=>$end,'blocker'=>null])->save();
            $this->event($t,'activated');
            return $t;
        });
    }
    public function stop(string $id, string $reason): TrialRequest
    {
        $this->gate();
        if (!in_array($reason,['cancelled','expired'],true)) { throw new DomainException('invalid_stop_reason'); }
        return DB::transaction(function () use ($id,$reason) {
            $t=TrialRequest::whereKey($id)->lockForUpdate()->firstOrFail();
            if (in_array($t->status,['cancelled','expired'],true)) { return $t; }
            $pending=$reason.'_pending';
            if ($t->status !== $pending) {
                $t->forceFill(['status'=>$pending,'cancelled_at'=>$reason==='cancelled'?Date::now('UTC'):null])->save();
                $this->event($t,$pending); // Access is denied immediately by status.
            }
            try { $this->provider->revoke($t->id); }
            catch (\Throwable $e) {
                $t->forceFill(['blocker'=>'revocation_pending'])->save();
                return $t;
            }
            $t->forceFill(['status'=>$reason,'blocker'=>null])->save();
            $this->event($t,$reason);
            return $t;
        });
    }
}

<?php
namespace App\Console\Commands;
use App\Assessor\TrialRequest;
use App\Assessor\TrialService;
use Illuminate\Console\Command;
final class AssessorTrialCommand extends Command
{
    protected $signature='assessor:trial {operation : provision|expire} {id?}';
    protected $description='HML only: provision approved synthetic requests or reconcile expiry/revocation';
    public function handle(TrialService $s): int
    {
        $s->gate();
        if ($this->argument('operation')==='provision' && $this->argument('id')) {
            $t=$s->provision($this->argument('id'));
            $this->info($t->status);
            return $t->status==='active'?self::SUCCESS:self::FAILURE;
        }
        if ($this->argument('operation')!=='expire') { return self::FAILURE; }
        TrialRequest::where(function ($q) {
            $q->where(function ($q) { $q->where('status','active')->where('ends_at','<=',now('UTC')); })
              ->orWhereIn('status',['cancelled_pending','expired_pending']);
        })->each(function ($t) use ($s) { $s->stop($t->id,$t->status==='cancelled_pending'?'cancelled':'expired'); });
        return TrialRequest::whereIn('status',['cancelled_pending','expired_pending'])->exists()?self::FAILURE:self::SUCCESS;
    }
}

<?php
namespace App\Http\Controllers;
use App\Assessor\TrialRequest;
use App\Assessor\TrialService;
use Illuminate\Http\Request;
final class AssessorTrialController extends Controller
{
    private function company(Request $r): int
    {
        abort_unless($r->user()->is_active && $r->user()->hasVerifiedEmail() && $r->user()->company_id,403);
        return (int)$r->user()->company_id;
    }
    public function index(Request $r)
    {
        $company=$this->company($r);
        return view('assessor.trial',['trial'=>TrialRequest::where('company_id',$company)->where('user_id',$r->user()->id)->first()]);
    }
    public function store(Request $r, TrialService $s)
    {
        $company=$this->company($r);
        $data=$r->validate(['cabinet_name'=>'required|string|max:120','mandate_type'=>'required|in:vereador,deputado',
            'consent'=>'accepted','terms_version'=>'required|in:'.config('assessor_trial.terms_version')]);
        try { $s->request($r->user()->id,$company,$data['cabinet_name'],$data['mandate_type']); }
        catch (\DomainException $e) { return back()->withErrors(['trial'=>'Avaliação indisponível ou já solicitada para este gabinete.']); }
        return redirect()->route('assessor.trial');
    }
    public function cancel(Request $r, string $id, TrialService $s)
    {
        TrialRequest::whereKey($id)->where('company_id',$this->company($r))->where('user_id',$r->user()->id)->firstOrFail();
        $s->stop($id,'cancelled');
        return redirect()->route('assessor.trial');
    }
}

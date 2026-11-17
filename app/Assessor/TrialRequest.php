<?php
namespace App\Assessor;
use Illuminate\Database\Eloquent\Model;
final class TrialRequest extends Model
{
    protected $table = 'assessor_trial_requests';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return ['cabinet_name'=>'encrypted', 'consented_at'=>'immutable_datetime',
            'starts_at'=>'immutable_datetime','ends_at'=>'immutable_datetime','cancelled_at'=>'immutable_datetime'];
    }
}

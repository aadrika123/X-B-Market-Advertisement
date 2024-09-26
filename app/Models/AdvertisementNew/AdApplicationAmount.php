<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AdApplicationAmount extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function saveApplicationRate($req, $AgencyId, $applicationTypeId)
    {
        $mRigRegistrationCharge = new AdApplicationAmount();
        $mRigRegistrationCharge->application_id     = $AgencyId;
        $mRigRegistrationCharge->charge_category    = $applicationTypeId;
        $mRigRegistrationCharge->amount             = $req->rate;
        $mRigRegistrationCharge->penalty            = 0;                                        // Static
        // $mRigRegistrationCharge->registration_fee   = $req->registrationFee;
        $mRigRegistrationCharge->created_at         = Carbon::now();
        $mRigRegistrationCharge->rebate             = 0;                                        // Static
        $mRigRegistrationCharge->paid_status        = $req->refPaidstatus ?? 0;
        $mRigRegistrationCharge->application_category_name = $req->applicationType;
        $mRigRegistrationCharge->save();
        return $mRigRegistrationCharge->id;
    }
    /**
     * | Get registration charges accordng to application id 
     */
    public function getChargesbyId($id)
    {
        return AdApplicationAmount::where('application_id', $id)
            ->where('status', 1)
            ->orderByDesc('id');
    }
}

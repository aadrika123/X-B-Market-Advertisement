<?php

namespace App\Models\AdvertisementNew;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdDirectApplicationAmount extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $_applicationDate;
    // Initializing construction
    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
    }
    public function saveApplicationRate($req, $AgencyId, $applicationTypeId)
    {
        $mRigRegistrationCharge = new AdDirectApplicationAmount();
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
        return AdDirectApplicationAmount::where('application_id', $id)
            ->where('status', 1)
            ->orderByDesc('id');
    }
}

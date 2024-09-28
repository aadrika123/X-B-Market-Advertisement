<?php

namespace App\Models\AdvertisementNew;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            ->where('paid_status', 0)
            ->orderByDesc('id');
    }
    public function getChargesbyIds($id)
    {
        $charge = AdDirectApplicationAmount::select(
            'ad_direct_application_amounts.amount',
        )
            ->where('application_id', $id)
            ->where('status', 1)
            ->where('paid_status', 0)
            ->orderByDesc('id')
           ->first();
        if ($charge && is_numeric($charge->amount)) {
            $amountInWords = getIndianCurrency($charge->amount) . " Only /-";
        } else {
            // Handle the case where amount is invalid
            throw new Exception("Invalid amount or amount not found.");
        }

        return [
            'amount' => $charge->amount,
            'amountInWords' => $amountInWords
        ];
    }
}

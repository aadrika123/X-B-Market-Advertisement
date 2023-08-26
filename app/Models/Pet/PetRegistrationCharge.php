<?php

namespace App\Models\Pet;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetRegistrationCharge extends Model
{
    use HasFactory;

    /**
     * | Save the pet registration charge
        | Caution 
     */
    public function saveRegisterCharges($req)
    {
        $mPetRegistrationCharge = new PetRegistrationCharge();
        $mPetRegistrationCharge->application_id     = $req->applicationId;
        $mPetRegistrationCharge->charge_category    = $req->applicationTypeId;
        $mPetRegistrationCharge->amount             = $req->amount;
        $mPetRegistrationCharge->penalty            = 0;                                        // Static
        $mPetRegistrationCharge->registration_fee   = $req->registrationFee;
        $mPetRegistrationCharge->created_at         = Carbon::now();
        $mPetRegistrationCharge->rebate             = 0;                                        // Static
        $mPetRegistrationCharge->paid_status        = $req->refPaidstatus ?? 0;
        $mPetRegistrationCharge->charge_category_name = $req->applicationType;
        $mPetRegistrationCharge->save();
    }

    /**
     * | Get registration charges accordng to application id 
     */
    public function getChargesbyId($id)
    {
        return PetRegistrationCharge::where('application_id', $id)
            ->where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | Delete the application cherges before payment  
     */
    public function deleteCharges($applicationId)
    {
        PetRegistrationCharge::where('application_id', $applicationId)
            ->delete();
    }

    /**
     * | Save payment status for payment
     */
    public function saveStatus($id, $refRequest)
    {
        PetRegistrationCharge::where('id', $id)
            ->update($refRequest);
    }
}

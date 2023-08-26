<?php

namespace App\Models\Pet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetActiveApplicant extends Model
{
    use HasFactory;

    /**
     * | Save the pet's applicants details  
     */
    public function saveApplicants($req, $applicaionId)
    {
        $mPetActiveApplicant = new PetActiveApplicant();
        $mPetActiveApplicant->mobile_no         = $req->mobileNo;
        $mPetActiveApplicant->email             = $req->email;
        $mPetActiveApplicant->pan_no            = $req->panNo;
        $mPetActiveApplicant->applicant_name    = $req->applicantName;
        $mPetActiveApplicant->uid               = $req->uid ?? null;
        $mPetActiveApplicant->telephone         = $req->telephone;
        $mPetActiveApplicant->voters_card_no    = $req->voterCard;
        $mPetActiveApplicant->owner_type        = $req->ownerCategory;
        $mPetActiveApplicant->application_id    = $applicaionId;
        $mPetActiveApplicant->save();
    }

    /**
     * | Get Details of owner by ApplicationId
     */
    public function getApplicationDetails($applicationId)
    {
        return PetActiveApplicant::where('application_id', $applicationId)
            ->where('status', 1)
            ->orderByDesc('id');
    }
}

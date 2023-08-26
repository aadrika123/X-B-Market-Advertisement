<?php

namespace App\Models\Pet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetActiveDetail extends Model
{
    use HasFactory;
    protected $fillable = [];

    /**
     * | Save pet active Details 
     */
    public function savePetDetails($req, $applicationId)
    {
        $mPetActiveDetail = new PetActiveDetail();
        $mPetActiveDetail->application_id           = $applicationId;
        $mPetActiveDetail->sex                      = $req->petGender;
        $mPetActiveDetail->identification_mark      = $req->petIdentity;
        $mPetActiveDetail->breed                    = $req->breed;
        $mPetActiveDetail->color                    = $req->color;
        $mPetActiveDetail->vet_doctor_name          = $req->doctorName;
        $mPetActiveDetail->doctor_registration_no   = $req->doctorRegNo;
        $mPetActiveDetail->rabies_vac_date          = $req->dateOfRabies;
        $mPetActiveDetail->leptospirosis_vac_date   = $req->dateOfLepVaccine;
        $mPetActiveDetail->dob                      = $req->petBirthDate;
        $mPetActiveDetail->pet_name                 = $req->petName;
        $mPetActiveDetail->pet_type                 = $req->petType;
        $mPetActiveDetail->save();
    }


    /**
     * | Get Pet details by applicationId
     */
    public function getPetDetailsByApplicationId($applicationId)
    {
        return PetActiveDetail::where('application_id', $applicationId)
            ->where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | Update the pet details according to id
     */
    public function updatePetDetails($req, $petDetails)
    {
        PetActiveDetail::where('id', $petDetails->id)
            ->update([
                "sex"                       => $req->petGender          ?? $petDetails->sex,
                "identification_mark"       => $req->petIdentity        ?? $petDetails->identification_mark,
                "breed"                     => $req->breed              ?? $petDetails->breed,
                "color"                     => $req->color              ?? $petDetails->color,
                "vet_doctor_name"           => $req->doctorName         ?? $petDetails->vet_doctor_name,
                "doctor_registration_no"    => $req->doctorRegNo        ?? $petDetails->doctor_registration_no,
                "rabies_vac_date"           => $req->dateOfRabies       ?? $petDetails->rabies_vac_date,
                "leptospirosis_vac_date"    => $req->dateOfLepVaccine   ?? $petDetails->leptospirosis_vac_date,
                "dob"                       => $req->petBirthDate       ?? $petDetails->dob,
                "pet_name"                  => $req->petName            ?? $petDetails->pet_name,
                "pet_type"                  => $req->petType            ?? $petDetails->pet_type
            ]);
    }
}

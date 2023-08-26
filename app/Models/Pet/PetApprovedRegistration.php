<?php

namespace App\Models\Pet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetApprovedRegistration extends Model
{
    use HasFactory;

    /**
     * | Get Approve registration by Id
     */
    public function getApproveRegById($registerId)
    {
        return PetApprovedRegistration::select('pet_active_registrations.*')
            ->join("pet_active_details", "pet_active_details.application_id", "pet_active_registrations.id")
            ->join("pet_active_applicants", "pet_active_applicants.application_id", "pet_active_registrations.id")
            ->where('pet_active_registrations.id', $registerId)
            ->where('pet_active_details.status', 1)
            ->where('pet_active_applicants.status', 1)
            ->where('pet_active_registrations.status', 1);
    }

    /**
     * | Deactivate the previous data for new Entry 
        | (CAUTION)
     */
    public function deactivateOldRegistration($registrationId)
    {
        PetApprovedRegistration::join('pet_approve_applicants', 'pet_approve_applicants.application_id', 'pet_approved_registrations.id')
            ->join('pet_approve_details', 'pet_approve_details.application_id', 'pet_approved_registrations.id')
            ->where('pet_approved_registrations.id', $registrationId)
            ->delete();
    }

    /**
     * | Get the approved application details by id
     */
    public function getApplictionByRegId($id)
    {
        return PetApprovedRegistration::join('pet_approve_applicants', 'pet_approve_applicants.application_id', 'pet_approved_registrations.id')
            ->join('pet_approve_details', 'pet_approve_details.application_id', 'pet_approved_registrations.id')
            ->where('pet_approved_registrations.id', $id);
    }
}

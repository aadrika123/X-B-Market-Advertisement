<?php

namespace App\Models\Pet;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class PetActiveRegistration extends Model
{
    use HasFactory;

    /**
     * | Save pet Registration 
     * | Application data saving
     */
    public function saveRegistration($req, $user)
    {
        $userType = Config::get("pet.REF_USER_TYPE");
        $mPetActiveRegistration = new PetActiveRegistration();

        $mPetActiveRegistration->renewal                = $req->isRenewal ?? 0;
        $mPetActiveRegistration->registration_id        = $req->registrationId ?? null;

        $mPetActiveRegistration->application_no         = $req->applicationNo;
        $mPetActiveRegistration->address                = $req->address;

        $mPetActiveRegistration->workflow_id            = $req->workflowId;
        $mPetActiveRegistration->initiator_role_id      = $req->initiatorRoleId;
        $mPetActiveRegistration->finisher_role_id       = $req->finisherRoleId;
        $mPetActiveRegistration->ip_address             = $req->ip();
        $mPetActiveRegistration->ulb_id                 = $req->ulbId;
        $mPetActiveRegistration->ward_id                = $req->ward;

        $mPetActiveRegistration->application_type       = $req->applicationType;                    // type new or renewal
        $mPetActiveRegistration->occurrence_type_id     = $req->petFrom;
        $mPetActiveRegistration->apply_through          = $req->applyThrough;                       // holding or saf
        $mPetActiveRegistration->owner_type             = $req->ownerCategory;
        $mPetActiveRegistration->application_type_id    = $req->applicationTypeId;

        $mPetActiveRegistration->created_at             = Carbon::now();
        $mPetActiveRegistration->application_apply_date = Carbon::now();

        $mPetActiveRegistration->holding_no             = $req->holdingNo ?? null;
        $mPetActiveRegistration->saf_no                 = $req->safNo ?? null;
        $mPetActiveRegistration->pet_type               = $req->petType;
        $mPetActiveRegistration->user_type              = $user->user_type;
        switch ($user->user_type) {
            case ($userType['1']):
                $mPetActiveRegistration->apply_mode = "ONLINE";                                     // Static
                $mPetActiveRegistration->citizen_id = $user->id;
                break;
            default:
                $mPetActiveRegistration->apply_mode = $user->user_type;
                $mPetActiveRegistration->user_id    = $req->userId;
                break;
        }
        $mPetActiveRegistration->save();
        return [
            "id" => $mPetActiveRegistration->id,
            "applicationNo" => $req->applicationNo
        ];
    }

    /**
     * | Get Application by applicationId
     */
    public function getPetApplicationById($applicationId)
    {
        return PetActiveRegistration::select(
            'pet_active_registrations.id as ref_application_id',
            'pet_active_details.id as ref_pet_id',
            'pet_active_applicants.id as ref_applicant_id',
            'pet_active_registrations.*',
            'pet_active_details.*',
            'pet_active_applicants.*',
            'pet_active_registrations.status as registrationStatus',
            'pet_active_details.status as petStatus',
            'pet_active_applicants.status as applicantsStatus',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            'm_pet_occurrence_types.occurrence_types'
        )
            ->join('m_pet_occurrence_types', 'm_pet_occurrence_types.id', 'pet_active_registrations.occurrence_type_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'pet_active_registrations.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'pet_active_registrations.ward_id')
            ->join('pet_active_applicants', 'pet_active_applicants.application_id', 'pet_active_registrations.id')
            ->join('pet_active_details', 'pet_active_details.application_id', 'pet_active_registrations.id')
            ->where('pet_active_registrations.id', $applicationId)
            ->where('pet_active_registrations.status', 1);
    }

    /**
     * | Deactivate the doc Upload Status 
     */
    public function updateUploadStatus($applicationId, $status)
    {
        PetActiveRegistration::where('id', $applicationId)
            ->where('status', 1)
            ->update([
                "doc_upload_status" => $status
            ]);
    }

    /**
     * | Get all details according to key 
     */
    public function getAllApplicationDetails($value, $key)
    {
        return DB::table('pet_active_registrations')
            ->join('pet_active_applicants', 'pet_active_applicants.application_id', 'pet_active_registrations.id')
            ->join('pet_active_details', 'pet_active_details.application_id', 'pet_active_registrations.id')
            ->where('pet_active_registrations.' . $key, $value)
            ->where('pet_active_registrations.status', 1);
    }


    /**
     * | Get all details according to key 
        | Remove
     */
    public function dummyApplicationDetails($value, $key)
    {
        return DB::table('pet_active_registrations')
            ->join('pet_active_applicants', 'pet_active_applicants.application_id', 'pet_active_registrations.id')
            ->join('pet_active_details', 'pet_active_details.application_id', 'pet_active_registrations.id')
            ->where('pet_active_registrations.' . $key, $value)
            ->where('pet_active_registrations.status', 2);
    }


    /**
     * | Delete the application before the payment 
     */
    public function deleteApplication($applicationId)
    {
        PetActiveRegistration::where('pet_active_registrations.id', $applicationId)
            ->where('pet_active_registrations.status', 1)
            ->delete();
    }

    /** 
     * | Update the Doc related status in active table 
     */
    public function updateDocStatus($applicationId, $status)
    {
        PetActiveRegistration::where('id', $applicationId)
            ->update([
                // 'doc_upload_status' => true,
                'doc_verify_status' => $status
            ]);
    }

    /**
     * | Save the status in Active table
     */
    public function saveApplicationStatus($applicationId, $refRequest)
    {
        PetActiveRegistration::where('id', $applicationId)
            ->update($refRequest);
    }
}

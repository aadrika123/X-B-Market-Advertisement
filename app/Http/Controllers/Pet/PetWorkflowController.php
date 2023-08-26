<?php

namespace App\Http\Controllers\Pet;

use App\Http\Controllers\Controller;
use App\Models\Advertisements\WfActiveDocument;
use App\Models\Pet\PetActiveRegistration;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\Workflows\WorkflowTrack;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PetWorkflowController extends Controller
{

    use Workflow;

    private $_masterDetails;
    private $_propertyType;
    private $_occupancyType;
    private $_workflowMasterId;
    private $_petParamId;
    private $_petModuleId;
    private $_userType;
    private $_petWfRoles;
    private $_docReqCatagory;
    private $_dbKey;
    private $_fee;
    private $_applicationType;
    private $_offlineVerificationModes;
    private $_paymentMode;
    private $_offlineMode;
    # Class constructer 
    public function __construct()
    {
        $this->_masterDetails           = Config::get("pet.MASTER_DATA");
        $this->_propertyType            = Config::get("pet.PROP_TYPE");
        $this->_occupancyType           = Config::get("pet.PROP_OCCUPANCY_TYPE");
        $this->_workflowMasterId        = Config::get("pet.WORKFLOW_MASTER_ID");
        $this->_petParamId              = Config::get("pet.PARAM_ID");
        $this->_petModuleId             = Config::get('pet.PET_MODULE_ID');
        $this->_userType                = Config::get("pet.REF_USER_TYPE");
        $this->_petWfRoles              = Config::get("pet.ROLE_LABEL");
        $this->_docReqCatagory          = Config::get("pet.DOC_REQ_CATAGORY");
        $this->_dbKey                   = Config::get("pet.DB_KEYS");
        $this->_fee                     = Config::get("pet.FEE_CHARGES");
        $this->_applicationType         = Config::get("pet.APPLICATION_TYPE");
        $this->_offlineVerificationModes = Config::get("pet.VERIFICATION_PAYMENT_MODES");
        $this->_paymentMode             = Config::get("pet.PAYMENT_MODE");
        $this->_offlineMode             = Config::get("pet.OFFLINE_PAYMENT_MODE");
    }


    /**
     * | Inbox
     * | workflow
        | Serial No :
        | Working
     */
    public function inbox(Request $request)
    {
        try {
            $user   = authUser($request);
            $userId = $user->id;
            $ulbId  = $user->ulb_id;
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();

            $occupiedWards = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $waterList = $this->getPetApplicatioList($workflowIds, $ulbId)
                ->whereIn('pet_active_registrations.current_role_id', $roleId)
                ->whereIn('pet_active_registrations.ward_id', $occupiedWards)
                ->where('pet_active_registrations.is_escalate', false)
                ->where('pet_active_registrations.parked', false)
                ->get();
            $filterWaterList = collect($waterList)->unique('id')->values();
            return responseMsgs(true, "Inbox List Details!", remove_null($filterWaterList), '', '02', '', 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | Common function
        | Move the function in trait 
        | Caution remove the function 
     */
    public function getPetApplicatioList($workflowIds, $ulbId)
    {
        return PetActiveRegistration::select(
            'pet_active_registrations.id',
            'pet_active_registrations.application_no',
            'pet_active_applicants.id as owner_id',
            'pet_active_applicants.applicant_name as owner_name',
            'pet_active_registrations.ward_id',
            'u.ward_name as ward_no',
            'pet_active_registrations.workflow_id',
            'pet_active_registrations.current_role_id as role_id',
            'pet_active_registrations.application_apply_date',
            'pet_active_registrations.parked',
            'pet_active_registrations.is_escalate'
        )
            ->join('ulb_ward_masters as u', 'u.id', '=', 'pet_active_registrations.ward_id')
            ->join('pet_active_applicants', 'pet_active_applicants.application_id', 'pet_active_registrations.id')
            ->join('pet_active_details', 'pet_active_details.application_id', 'pet_active_registrations.id')
            ->where('pet_active_registrations.status', 1)
            // ->where('pet_active_registrations.payment_status', 1)
            ->where('pet_active_registrations.ulb_id', $ulbId)
            ->whereIn('pet_active_registrations.workflow_id', $workflowIds)
            ->orderByDesc('pet_active_applicants.id');
    }


    /**
     * | OutBox
     * | Outbox details for display
        | Serial No :
        | Working
     */
    public function outbox(Request $req)
    {
        try {
            $user                   = authUser($req);
            $userId                 = $user->id;
            $ulbId                  = $user->ulb_id;
            $mWfWorkflowRoleMaps    = new WfWorkflowrolemap();

            $occupiedWards  = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId         = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds    = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $waterList = $this->getPetApplicatioList($workflowIds, $ulbId)
                ->whereNotIn('pet_active_registrations.current_role_id', $roleId)
                ->whereIn('pet_active_registrations.ward_id', $occupiedWards)
                ->orderByDesc('pet_active_registrations.id')
                ->get();
            $filterWaterList = collect($waterList)->unique('id')->values();
            return responseMsgs(true, "Outbox List", remove_null($filterWaterList), '', '01', '.ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }


    /**
     * | Post next level in workflow 
        | Serial No :
        | Check for forward date and backward date
     */
    public function postNextLevel(Request $req)
    {
        $wfLevels = $this->_petWfRoles;
        $validated = Validator::make(
            $req->all(),
            [
                'applicationId'     => 'required',
                'senderRoleId'      => 'nullable',
                'receiverRoleId'    => 'nullable',
                'action'            => 'required|In:forward,backward',
                'comment'           => $req->senderRoleId == $wfLevels['BO'] ? 'nullable' : 'required',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWfRoleMaps        = new WfWorkflowrolemap();
            $current            = Carbon::now();
            $wfLevels           = $wfLevels;
            $petApplication     = PetActiveRegistration::findOrFail($req->applicationId);

            # Derivative Assignments
            $senderRoleId = $petApplication->current_role_id;
            $ulbWorkflowId = $petApplication->workflow_id;
            $ulbWorkflowMaps = WfWorkflow::findOrFail($ulbWorkflowId);
            $roleMapsReqs = new Request([
                'workflowId' => $ulbWorkflowMaps->id,
                'roleId' => $senderRoleId
            ]);
            $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);

            DB::beginTransaction();
            if ($req->action == 'forward') {
                $this->checkPostCondition($req->senderRoleId, $wfLevels, $petApplication);            // Check Post Next level condition
                $metaReqs['verificationStatus']     = 1;
                $metaReqs['receiverRoleId']         = $forwardBackwardIds->forward_role_id;
                $petApplication->current_role_id    = $forwardBackwardIds->forward_role_id;
                $petApplication->last_role_id       = $forwardBackwardIds->forward_role_id;                                      // Update Last Role Id
            }
            if ($req->action == 'backward') {
                $petApplication->current_role_id   = $forwardBackwardIds->backward_role_id;
                $metaReqs['verificationStatus'] = 0;
                $metaReqs['receiverRoleId']     = $forwardBackwardIds->backward_role_id;
            }
            $petApplication->save();

            $metaReqs['moduleId']           = $this->_petModuleId;
            $metaReqs['workflowId']         = $petApplication->workflow_id;
            $metaReqs['refTableDotId']      = 'pet_active_registrations.id';                                                // Static
            $metaReqs['refTableIdValue']    = $req->applicationId;
            $metaReqs['user_id']            = authUser($req)->id;
            $req->request->add($metaReqs);

            $waterTrack = new WorkflowTrack();
            $waterTrack->saveTrack($req);

            # Check in all the cases the data if entered in the track table 
            # Updation of Received Date
            // $preWorkflowReq = [
            //     'workflowId'        => $petApplication->workflow_id,
            //     'refTableDotId'     => "pet_active_registrations.id",
            //     'refTableIdValue'   => $req->applicationId,
            //     'receiverRoleId'    => $senderRoleId
            // ];

            // $previousWorkflowTrack = $waterTrack->getWfTrackByRefId($preWorkflowReq);
            // $previousWorkflowTrack->update([
            //     'forward_date' => $current->format('Y-m-d'),
            //     'forward_time' => $current->format('H:i:s')
            // ]);
            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", "", '01', '.ms', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }


    /**
     * | Check the condition before forward
        | Serial No :
        | Under Construction
     */
    public function checkPostCondition($senderRoleId, $wfLevels, $application)
    {
        switch ($senderRoleId) {
            case $wfLevels['BO']:                                                                       // Back Office Condition
                if ($application->doc_upload_status == false || $application->payment_status != 1)
                    throw new Exception("Document Not Fully Uploaded or Payment in not Done!");
                break;
            case $wfLevels['DA']:
                if ($application->doc_upload_status == false || $application->payment_status != 1)
                    throw new Exception("Document Not Fully Uploaded or Payment in not Done!");                                                                      // DA Condition
                if ($application->doc_verify_status == false)
                    throw new Exception("Document Not Fully Verified!");
                break;
        }
    }


    /**
     * | Verify, Reject document 
     */
    public function docVerifyRejects(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'id'            => 'required|digits_between:1,9223372036854775807',
                'applicationId' => 'required|digits_between:1,9223372036854775807',
                'docRemarks'    =>  $req->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
                'docStatus'     => 'required|in:Verified,Rejected'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            # Variable Assignments
            $mWfDocument                = new WfActiveDocument();
            $mPetActiveRegistration     = new PetActiveRegistration();
            $mWfRoleusermap             = new WfRoleusermap();
            $wfDocId                    = $req->id;
            $applicationId              = $req->applicationId;
            $userId                     = authUser($req)->id;
            $wfLevel                    = $this->_petWfRoles;

            # validating application
            $petApplicationDtl = $mPetActiveRegistration->getPetApplicationById($applicationId)
                ->first();
            if (!$petApplicationDtl || collect($petApplicationDtl)->isEmpty())
                throw new Exception("Application Details Not Found");

            # validating roles
            $waterReq = new Request([
                'userId'        => $userId,
                'workflowId'    => $petApplicationDtl['workflow_id']
            ]);
            $senderRoleDtls = $mWfRoleusermap->getRoleByUserWfAndId($waterReq);
            if (!$senderRoleDtls || collect($senderRoleDtls)->isEmpty())
                throw new Exception("Role Not Available");

            # validating role for DA
            $senderRoleId = $senderRoleDtls->wf_role_id;
            if ($senderRoleId != $wfLevel['DA'])                                    // Authorization for Dealing Assistant Only
                throw new Exception("You are not Authorized");

            # validating if full documet is uploaded
            $ifFullDocVerified = $this->ifFullDocVerified($applicationId);          // (Current Object Derivative Function 0.1)
            if ($ifFullDocVerified == 1)
                throw new Exception("Document Fully Verified");

            DB::beginTransaction();
            if ($req->docStatus == "Verified") {
                $status = 1;
            }
            if ($req->docStatus == "Rejected") {
                # For Rejection Doc Upload Status and Verify Status will disabled 
                $status = 2;
                $petApplicationDtl->doc_upload_status = 0;
                $petApplicationDtl->save();
            }
            $reqs = [
                'remarks'           => $req->docRemarks,
                'verify_status'     => $status,
                'action_taken_by'   => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            if ($req->docStatus == 'Verified')
                $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId);
            else
                $ifFullDocVerifiedV1 = 0;

            if ($ifFullDocVerifiedV1 == 1) {                                        // If The Document Fully Verified Update Verify Status
                $status = true;
                $mPetActiveRegistration->updateDocStatus($applicationId, $status);
            }
            DB::commit();
            return responseMsgs(true, $req->docStatus . " Successfully", "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Check if the Document is Fully Verified or Not (0.1) | up
     * | @param
     * | @var 
     * | @return
        | Serial No :  
        | Working 
     */
    public function ifFullDocVerified($applicationId)
    {
        $mPetActiveRegistration = new PetActiveRegistration();
        $mWfActiveDocument      = new WfActiveDocument();
        $refapplication = $mPetActiveRegistration->getPetApplicationById($applicationId)
            ->firstOrFail();

        $refReq = [
            'activeId'      => $applicationId,
            'workflowId'    => $refapplication['workflow_id'],
            'moduleId'      => $this->_petModuleId,
        ];

        $req = new Request($refReq);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        $ifPropDocUnverified = $refDocList->contains('verify_status', 0);
        if ($ifPropDocUnverified == true)
            return 0;
        else
            return 1;
    }


    /**
     * | Get details for the pet special inbox
        | Serial No :
        | Under Con
     */
    public function waterSpecialInbox(Request $request)
    {
        try {
            $user   = authUser($request);
            $userId = $user->id;
            $ulbId  = $user->ulb_id;
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();

            $occupiedWards = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $waterList = $this->getPetApplicatioList($workflowIds, $ulbId)
                ->whereIn('pet_active_registrations.ward_id', $occupiedWards)
                ->where('pet_active_registrations.is_escalate', true)
                ->get();
            $filterWaterList = collect($waterList)->unique('id')->values();
            return responseMsgs(true, "Inbox List Details!", remove_null($filterWaterList), '', '02', '', 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $request->deviceId);
        }
    }



    /**
     * | default final applroval 
        | remove
     */
    public function finalVerificationRejection(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'applicationId' => 'required|digits_between:1,9223372036854775807',
                'status' => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $userId                 = authUser($req)->id;
            $applicationId          = $req->applicationId;
            $mPetActiveRegistration = new PetActiveRegistration();
            $mWfRoleUsermap         = new WfRoleusermap();
            $currentDateTime        = Carbon::now();

            $application = $mPetActiveRegistration->getPetApplicationById($applicationId)->firstOrFail();
            $workflowId = $application->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;
            if ($roleId != $application->finisher_role_id) {
                throw new Exception("You are not the Finisher!");
            }
            if ($application->doc_upload_status == false || $application->payment_status != 1)
                throw new Exception("Document Not Fully Uploaded or Payment in not Done!");                                                                      // DA Condition
            if ($application->doc_verify_status == false)
                throw new Exception("Document Not Fully Verified!");

            # Change the concept 
            if ($req->status == 1) {
                $regNo = "PET" . Carbon::createFromDate()->milli . carbon::now()->diffInMicroseconds() . strtotime($currentDateTime);
                PetActiveRegistration::where('id', $applicationId)
                    ->update([
                        "status" => 2,
                        "registration_no" => $regNo
                    ]);
                $returnData = [
                    "applicationId" => $application->application_no,
                    "registration_no" => $regNo
                ];
                return responseMsgs(true, 'Pet registration Application Approved!', $returnData);
            } else {
                PetActiveRegistration::where('id', $applicationId)
                    ->update([
                        "status" => 0,
                    ]);
                return responseMsgs(true, 'Pet registration Application Rejected!', $application->application_no);
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}

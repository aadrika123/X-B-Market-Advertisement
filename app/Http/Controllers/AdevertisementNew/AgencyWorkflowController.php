<?php

namespace App\Http\Controllers\AdevertisementNew;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\AdvDetailsTraits;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Repositories\SelfAdvets\iSelfAdvetRepo;
use App\Models\Workflows\WorkflowTrack;
use App\Traits\WorkflowTrait;
use App\Models\Workflows\WfRoleusermap;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Models\AdvertisementNew\AgencyMaster;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use App\Http\Requests\AgencyNew\AddNewAgency;
use App\MicroServices\DocumentUpload;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\AdvertisementNew\AdvertisementType;
use App\Models\AdvertisementNew\AdvertiserMaster;
use App\Models\AdvertisementNew\AgencyHoarding;
use App\Models\AdvertisementNew\BrandMaster;
use App\Models\Advertisements\AdvActiveAgency;
use App\Models\Advertisements\WfActiveDocument;
use Illuminate\Support\Facades\Validator;
use App\Models\AdvertisementNew\HoardingMaster;
use App\Models\AdvertisementNew\Location;
use App\Models\Advertisements\AdvActiveHoarding;
use App\Models\Advertisements\RefRequiredDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\Workflows\CustomDetail;
use App\Models\Workflows\UlbWardMaster;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WorkflowMap;

class AgencyWorkflowController extends Controller

{
    use AdvDetailsTraits;
    use WorkflowTrait;
    protected $_modelObj;
    protected $Repository;
    protected $_workflowIds;
    protected $_moduleId;
    protected $_docCode;
    protected $_tempParamId;
    protected $_paramId;
    protected $_baseUrl;
    protected $_docUrl;
    protected $_wfMasterId;
    protected $_fileUrl;
    protected $_hoarObj;
    protected $_brandObj;
    protected $_advertObj;
    protected $_locatObj;
    protected $_advObj;
    protected $_agencyObj;
    protected $_activeHObj;
    protected $_applicationDate;
    protected $_userType;
    protected $_docReqCatagory;
    protected $_wfroles;

    public function __construct()
    {
        $this->_modelObj = new AgencyMaster();
        $this->_hoarObj  = new HoardingMaster();
        $this->_brandObj = new BrandMaster();
        $this->_advertObj = new AdvertisementType();
        $this->_locatObj  = new Location();
        $this->_advObj     = new AdvertiserMaster();
        $this->_agencyObj = new AgencyHoarding();
        $this->_activeHObj = new AdvActiveHoarding();
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
        // $this->_workflowIds = Config::get('workflow-constants.AGENCY_WORKFLOWS');
        $this->_moduleId = Config::get('workflow-constants.ADVERTISMENT_MODULE');
        $this->_docCode = Config::get('workflow-constants.AGENCY_DOC_CODE');
        $this->_tempParamId = Config::get('workflow-constants.TEMP_AG_ID');
        $this->_paramId = Config::get('workflow-constants.AGY_ID');
        $this->_baseUrl = Config::get('constants.BASE_URL');
        $this->_docUrl = Config::get('workflow-constants.DOC_URL');
        $this->_fileUrl = Config::get('workflow-constants.FILE_URL');
        $this->_userType            = Config::get("workflow-constants.REF_USER_TYPE");
        $this->_docReqCatagory      = Config::get("workflow-constants.DOC_REQ_CATAGORY");
        $this->_wfroles             = Config::get('workflow-constants.ROLE_LABEL');
        // $this->Repository = $agency_repo;

        $this->_wfMasterId = Config::get('workflow-constants.AGENCY_WF_MASTER_ID');
    }

    public function listInbox(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'perPage' => 'nullable|integer',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user                   = authUser($req);
            $pages                  = $req->perPage ?? 10;
            $userId                 = $user->id;
            $ulbId                  = $user->ulb_id;
            $mWfWorkflowRoleMaps    = new WfWorkflowrolemap();

            $occupiedWards  = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId         = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds    = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $inboxDetails = $this->getConsumerWfBaseQuerry($workflowIds, $ulbId)
                ->whereIn('agency_hoardings.current_role_id', $roleId)
                ->where('agency_hoardings.is_escalate', false)
                ->where('agency_hoardings.parked', false)
                ->where('agency_hoardings.approve', 0)
                ->orderByDesc('agency_hoardings.id')
                ->paginate($pages);

            $isDataExist = collect($inboxDetails)->last();
            if (!$isDataExist || $isDataExist == 0) {
                throw new Exception('Data not Found!');
            }
            return responseMsgs(true, "Successfully listed consumer req inbox details!", $inboxDetails, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $req->deviceId);
        }
    }
    /**
     * | common function for workflow
     * | Get consumer active application details 
        | Serial No : 04
        | Working
     */
    public function getConsumerWfBaseQuerry($workflowIds, $ulbId)
    {
        return AgencyHoarding::select(
            'agency_hoardings.*',
            'agency_masters.agency_name as agencyName'
        )
            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->where('agency_hoardings.status', true)
            ->where('agency_hoardings.ulb_id', $ulbId)
            ->whereIn('agency_hoardings.workflow_id', $workflowIds);
    }

    public function listOutbox(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'perPage' => 'nullable|integer',
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $user                   = authUser($req);
            $pages                  = $req->perPage ?? 10;
            $userId                 = $user->id;
            $ulbId                  = $user->ulb_id;
            $mWfWorkflowRoleMaps    = new WfWorkflowrolemap();

            $occupiedWards  = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId         = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds    = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $inboxDetails = $this->getConsumerWfBaseQuerry($workflowIds, $ulbId)
                ->whereNotIn('agency_hoardings.current_role_id', $roleId)
                ->where('agency_hoardings.is_escalate', false)
                ->where('agency_hoardings.parked', false)
                ->orderByDesc('agency_hoardings.id')
                ->paginate($pages);

            $isDataExist = collect($inboxDetails)->last();
            if (!$isDataExist || $isDataExist == 0) {
                throw new Exception('Data not Found!');
            }
            return responseMsgs(true, "Successfully listed consumer req inbox details!", $inboxDetails, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $req->deviceId);
        }
    }
     /**
     * |---------------------------- Filter The Document For Viewing ----------------------------|
     * | @param documentList
     * | @param refWaterApplication
     * | @param ownerId
     * | @var mWfActiveDocument
     * | @var applicationId
     * | @var workflowId
     * | @var moduleId
     * | @var uploadedDocs
     * | Calling Function 01.01.01/ 01.02.01
        | Serial No : 
     */
    public function filterDocument($documentList, $refWaterApplication, $ownerId = null)
    {
        $mWfActiveDocument  = new WfActiveDocument();
        $applicationId      = $refWaterApplication->id;
        $workflowId         = $refWaterApplication->workflow_id;
        $moduleId            = Config::get('workflow-constants.ADVERTISMENT_MODULE');
        $uploadedDocs        = $mWfActiveDocument->getDocByRefIds($applicationId, $workflowId, $moduleId);

        $explodeDocs = collect(explode('#', $documentList->requirements));
        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs, $ownerId, $documentList) {

            # var defining
            $document   = explode(',', $explodeDoc);
            $key        = array_shift($document);
            $label      = array_shift($document);
            $documents  = collect();

            collect($document)->map(function ($item) use ($uploadedDocs, $documents, $ownerId, $documentList) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $item)
                    ->where('owner_dtl_id', $ownerId)
                    ->first();
                if ($uploadedDoc) {
                    $path = $this->readDocumentPath($uploadedDoc->doc_path);
                    $fullDocPath = !empty(trim($uploadedDoc->doc_path)) ? $path : null;
                    $response = [
                        "uploadedDocId" => $uploadedDoc->id ?? "",
                        "documentCode"  => $item,
                        "ownerId"       => $uploadedDoc->owner_dtl_id ?? "",
                        "docPath"       => $fullDocPath ?? "",
                        "verifyStatus"  => $uploadedDoc->verify_status ?? "",
                        "remarks"       => $uploadedDoc->remarks ?? "",
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType']      = $key;
            $reqDoc['uploadedDoc']  = $documents->last();
            $reqDoc['docName']      = substr($label, 1, -1);
            // $reqDoc['refDocName'] = substr($label, 1, -1);

            $reqDoc['masters'] = collect($document)->map(function ($doc) use ($uploadedDocs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                if (isset($uploadedDoc)) {
                    $path =  $this->readDocumentPath($uploadedDoc->doc_path);
                    $fullDocPath = !empty(trim($uploadedDoc->doc_path)) ? $path : null;
                }
                $arr = [
                    "documentCode"  => $doc,
                    "docVal"        => ucwords($strReplace),
                    "uploadedDoc"   => $fullDocPath ?? "",
                    "uploadedDocId" => $uploadedDoc->id ?? "",
                    "verifyStatus'" => $uploadedDoc->verify_status ?? "",
                    "remarks"       => $uploadedDoc->remarks ?? "",
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return $filteredDocs;
    }
    /**
     * 
     */
    public function getDocList(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'applicationId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mgemncyHoardApplication  = new AgencyHoarding();
            // $mWaterApplicant    = new WaterApplicant();

            $refhoardApplication = $mgemncyHoardApplication->checkdtlsById($req->applicationId);                      // Get Saf Details
            if (!$refhoardApplication) {
                throw new Exception("Application Not Found for this id");
            }
            // $refWaterApplicant = $mWaterApplicant->getOwnerList($req->applicationId)->get();
            $documentList = $this->getAgencyDocLists($refhoardApplication, $req);
            $hoardTypeDocs['listDocs'] = collect($documentList)->map(function ($value, $key) use ($refhoardApplication) {
                return $this->filterDocument($value, $refhoardApplication)->first();
            });

            $totalDocLists = collect($hoardTypeDocs); //->merge($waterOwnerDocs);
            $totalDocLists['docUploadStatus'] = $refhoardApplication->doc_upload_status;
            $totalDocLists['docVerifyStatus'] = $refhoardApplication->doc_status;
            return responseMsgs(true, "", remove_null($totalDocLists), "010203", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", "", 'POST', "");
        }
    }
     /**
     * |---------------------------- List of the doc to upload ----------------------------|
     * | Calling function
     * | 01.01
        | Serial No :  
     */
    public function getAgencyDocLists($application, $req)
    {
        // $user           = authUser($req);
        $mRefReqDocs    = new RefRequiredDocument();
        $moduleId       = Config::get('workflow-constants.ADVERTISMENT_MODULE');
        $refUserType    = Config::get('workflow-constants.REF_USER_TYPE');

        $type = ["Hording_content"];

        // // Check if user_type is not equal to 1
        // if ($user->user_type == $refUserType['1']) {
        //     // Modify $type array for user_type not equal to 1
        //     $type = ["Hording_content"];
        // }

        return $mRefReqDocs->getCollectiveDocByCode($moduleId, $type);
    }

    /**
     * | document upload for hoarding register by agency 
     */
    public function uploadDocument(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                "applicationId" => "required|numeric",
                "document"      => "required|mimes:pdf,jpeg,png,jpg|max:2048",
                "docCode"       => "required",
                "docCategory"   => "required",                                  // Recheck in case of undefined
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user                       = authUser($req);
            $metaReqs                   = array();
            $applicationId              = $req->applicationId;
            $document                   = $req->document;
            $refDocUpload               = new DocumentUpload;
            $mWfActiveDocument          = new WfActiveDocument();
            $magencyHoard               = new AgencyHoarding();
            $relativePath               = Config::get('constants.AGENCY_ADVET');
            $moduleId                   = Config::get('workflow-constants.ADVERTISMENT_MODULE');
            $confUserType               = $this->_userType;

            $getAgencyDetails  = $magencyHoard->getApplicationId($applicationId)->firstOrFail();
            $refImageName   = $req->docCode;
            $refImageName = $getAgencyDetails->id . '-' . str_replace(' ', '_', $refImageName);
            $imageName      = $refDocUpload->upload($refImageName, $document, $relativePath['RELATIVE_PATH']);

            $metaReqs = [
                'moduleId'      => $moduleId,
                'activeId'      => $getAgencyDetails->id,
                'workflowId'    => $getAgencyDetails->workflow_id,
                'ulbId'         => $getAgencyDetails->ulb_id,
                'relativePath'  => $relativePath['RELATIVE_PATH'],
                'document'      => $imageName,
                'docCode'       => $req->docCode,
                'ownerDtlId'    => $req->ownerId ?? null,
                'docCategory'   => $req->docCategory
            ];
            if ($user->user_type == $confUserType['1']) {
                $isCitizen = true;
                $this->checkParamForDocUpload($isCitizen, $getAgencyDetails, $user);
            } else {
                $isCitizen = false;
                $this->checkParamForDocUpload($isCitizen, $getAgencyDetails, $user);
            }

            DB::beginTransaction();
            $ifDocExist = $mWfActiveDocument->isDocCategoryExists($getAgencyDetails->ref_application_id, $getAgencyDetails->workflow_id, $moduleId, $req->docCategory, $req->ownerId);   // Checking if the document is already existing or not
            $metaReqs = new Request($metaReqs);
            if (collect($ifDocExist)->isEmpty()) {
                $mWfActiveDocument->postAgencyDocuments($metaReqs);
            }
            if ($ifDocExist) {
                $mWfActiveDocument->editDocuments($ifDocExist, $metaReqs);
            }
            #check full doc upload
            $refCheckDocument = $this->checkFullDocUpload($req);

            if ($refCheckDocument->contains(false) && $getAgencyDetails->doc_upload_status == true) {
                $getAgencyDetails->updateUploadStatus($applicationId, false);
            }
            if ($refCheckDocument->unique()->count() === 1 && $refCheckDocument->unique()->first() === true) {
                $getAgencyDetails->updateUploadStatus($req->applicationId, true);
            }

            DB::commit();
            return responseMsgs(true, "Document Uploadation Successful", "", "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * |Get the upoaded docunment
        | Serial No : 
        | Working
     */
    public function getUploadDocuments(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'applicationId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mHoardApplication = new AgencyHoarding();
            $moduleId          = Config::get('workflow-constants.ADVERTISMENT_MODULE');

            $hoardDetails = $mHoardApplication->checkdtlsById($req->applicationId)->first();
            if (!$hoardDetails)
                throw new Exception("Application Not Found for this application Id");

            $workflowId = $hoardDetails->workflow_id;

            $documents = $mWfActiveDocument->getagencyDocsByAppNo($req->applicationId, $workflowId, $moduleId)->get();
            $returnData = collect($documents)->map(function ($value) {                          // Static
                $path =  $this->readDocumentPath($value->ref_doc_path);
                $value->doc_path = !empty(trim($value->ref_doc_path)) ? trim($path, "/") : null;
                return $value;
            });
            return responseMsgs(true, "Uploaded Documents", remove_null($returnData), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**
     * |assign hoarding to agency 
     */
    public function assignAgency(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'roleId' => 'required|numeric',
                'userId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $roleId         = $req->roleId;
            $userId         = $req->userId;
            $hoardDetails = $this->_hoarObj->checkHoardById($userId);
            if (!$hoardDetails)
                throw new Exception("Application Not Found for this application Id");
            $agencyHoarding =  $this->_hoarObj->assignAgency($roleId, $userId);
            return responseMsgs(true, "agency assiggned", remove_null($agencyHoarding), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Post next level in workflow 
        | Serial No :
        | Check for forward date and backward date
     */
    public function postNextLevel(Request $req)
    {
        $wfLevels =  $this->_wfroles;
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
            $mHoardApplication     = AgencyHoarding::findOrFail($req->applicationId);

            # Derivative Assignments
            $senderRoleId = $mHoardApplication->current_role_id;
            $ulbWorkflowId = $mHoardApplication->workflow_id;
            $ulbWorkflowMaps = WfWorkflow::findOrFail($ulbWorkflowId);
            $roleMapsReqs = new Request([
                'workflowId' => $ulbWorkflowMaps->id,
                'roleId' => $senderRoleId
            ]);
            $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);

            DB::beginTransaction();
            if ($req->action == 'forward') {
                $this->checkPostCondition($req->senderRoleId, $wfLevels, $mHoardApplication);            // Check Post Next level condition
                $metaReqs['verificationStatus']     = 1;
                $metaReqs['receiverRoleId']         = $forwardBackwardIds->forward_role_id;
                $mHoardApplication->current_role_id    = $forwardBackwardIds->forward_role_id;
                $mHoardApplication->last_role_id       = $forwardBackwardIds->forward_role_id;                                      // Update Last Role Id
            }
            if ($req->action == 'backward') {
                $mHoardApplication->current_role_id   = $forwardBackwardIds->backward_role_id;
                $metaReqs['verificationStatus'] = 0;
                $metaReqs['receiverRoleId']     = $forwardBackwardIds->backward_role_id;
            }
            $mHoardApplication->save();

            $metaReqs['moduleId']           = $this->_moduleId;
            $metaReqs['workflowId']         = $mHoardApplication->workflow_id;
            $metaReqs['refTableDotId']      = 'pet_active_registrations.id';                                                // Static
            $metaReqs['refTableIdValue']    = $req->applicationId;
            $metaReqs['user_id']            = authUser($req)->id;
            $req->request->add($metaReqs);

            $waterTrack = new WorkflowTrack();
            $waterTrack->saveTrack($req);

            # Check in all the cases the data if entered in the track table 
            # Updation of Received Date
            // $preWorkflowReq = [
            //     'workflowId'        => $mHoardApplication->workflow_id,
            //     'refTableDotId'     => "agency_hoardngs.id",
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
                if ($application->doc_upload_status == false)
                    throw new Exception("Document Not Fully Uploaded ");
                break;
            case $wfLevels['DA']:
                if ($application->doc_upload_status == false)
                    throw new Exception("Document Not Fully Uploaded ");                                                                      // DA Condition
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
            $mAgencyHoard               = new AgencyHoarding();
            $mWfRoleusermap             = new WfRoleusermap();
            $wfDocId                    = $req->id;
            $applicationId              = $req->applicationId;
            $userId                     = authUser($req)->id;
            $wfLevel                    = $this->_wfroles;

            # validating application
            $applicationDtl = $mAgencyHoard->getApplicationId($applicationId)
                ->first();
            if (!$applicationDtl || collect($applicationDtl)->isEmpty())
                throw new Exception("Application Details Not Found");

            # validating roles
            $waterReq = new Request([
                'userId'        => $userId,
                'workflowId'    => $applicationDtl['workflow_id']
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
                $applicationDtl->doc_upload_status = 0;
                $applicationDtl->save();
            }
            $reqs = [
                'remarks'           => $req->docRemarks,
                'verify_status'     => $status,
                'action_taken_by'   => $userId
            ];
            $mWfDocument->docVerifyRejectv2($wfDocId, $reqs);
            if ($req->docStatus == 'Verified')
                $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId);
            else
                $ifFullDocVerifiedV1 = 0;

            if ($ifFullDocVerifiedV1 == 1) {                                        // If The Document Fully Verified Update Verify Status
                $status = true;
                $mAgencyHoard->updateDocStatus($applicationId, $status);
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
        $mAgencyHoard           = new AgencyHoarding();
        $mWfActiveDocument      = new WfActiveDocument();
        $refapplication = $mAgencyHoard->getApplicationId($applicationId)
            ->firstOrFail();

        $refReq = [
            'activeId'      => $applicationId,
            'workflowId'    => $refapplication['workflow_id'],
            'moduleId'      =>  $this->_moduleId,
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
     * get all  applications details by id from workflow
        |working ,not completed
     */
    public function getWorkflow(Request $request)
    {

        $request->validate([
            'applicationId' => "required"

        ]);

        try {
            return $this->getApplicationsDetails($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    public function getApplicationsDetails($request)
    {

        $forwardBackward        = new WorkflowMap();
        $mWorkflowTracks        = new WorkflowTrack();
        $mCustomDetails         = new CustomDetail();
        $mUlbNewWardmap         = new UlbWardMaster();
        $mAgencyHoard           = new AgencyHoarding();
        $mwaterOwner            = new AgencyMaster();
        $mhoardMaster           = new HoardingMaster();
        # applicatin details
        $applicationDetails = $mAgencyHoard->getFullDetails($request)->get();
        if (collect($applicationDetails)->first() == null) {
            return responseMsg(false, "Application Data Not found!", $request->applicationId);
        }
        # Ward Name
        $refApplication = collect($applicationDetails)->first();
        // $wardDetails = $mUlbNewWardmap->getWard($refApplication->ward_id);
        $aplictionList = [
            'application_no' => collect($applicationDetails)->first()->application_no,
            'apply_date' => collect($applicationDetails)->first()->allotment_date
        ];
        # DataArray
        $basicDetails = $this->getBasicDetails($applicationDetails);

        $firstView = [
            'headerTitle' => 'Basic Details',
            'data' => $basicDetails
        ];
        $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$firstView]);
        # CardArray
        $cardDetails = $this->getCardDetails($applicationDetails);
        $cardData = [
            'headerTitle' => 'Agency Hoarding',
            'data' => $cardDetails
        ];
        $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardData);
        # TableArray
        // $ownerList = $this->getOwnerDetails($ownerDetail);
        // $ownerView = [
        //     'headerTitle' => 'Owner Details',
        //     'tableHead' => ["#", "Owner Name", "Guardian Name", "Mobile No", "Email", "City", "District"],
        //     'tableData' => $ownerList
        // ];
        // $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerView]);

        # Level comment
        $mtableId = $applicationDetails->first()->id;
        $mRefTable = "agency_hoardings.id";
        $levelComment['levelComment'] = $mWorkflowTracks->getTracksByRefId($mRefTable, $mtableId);

        #citizen comment
        // $refCitizenId = $applicationDetails->first()->citizen_id;
        // $citizenComment['citizenComment'] = $mWorkflowTracks->getCitizenTracks($mRefTable, $mtableId, $refCitizenId);

        # Role Details
        $data = json_decode(json_encode($applicationDetails->first()), true);
        $metaReqs = [
            'customFor' => 'Agency Hoardings',
            'wfRoleId' => $data['current_role_id'],
            'workflowId' => $data['workflow_id'],
            'lastRoleId' => $data['last_role_id']
        ];
        $request->request->add($metaReqs);
        $forwardBackward = $forwardBackward->getRoleDetails($request);
        $roleDetails['roleDetails'] = collect($forwardBackward)->has('original') ? collect($forwardBackward)['original']['data'] : null;


        # Timeline Data
        $timelineData['timelineData'] = collect($request);

        # Departmental Post
        $custom = $mCustomDetails->getCustomDetails($request);
        $departmentPost['departmentalPost'] = collect($custom)->has('original') ? collect($forwardBackward)['original']['data'] : null;
        # Payments Details
        $returnValues = array_merge($aplictionList, $fullDetailsData, $levelComment, $timelineData, $roleDetails, $departmentPost);
        return responseMsgs(true, "listed Data!", remove_null($returnValues), "", "02", ".ms", "POST", "");
    }
    /**
     * function for return data of basic details
     */
    public function getBasicDetails($applicationDetails)
    {
        $collectionApplications = collect($applicationDetails)->first();
        return new Collection([
            ['displayString' => 'Agency Name',            'key' => 'agencyName',              'value' => $collectionApplications->agencyName],
            // ['displayString' => 'Ubl Id',                  'key' => 'ulbId',               'value' => $collectionApplications->ulb_id],
            ['displayString' => 'ApplyDate',               'key' => 'applyDate',          'value' => $collectionApplications->apply_date],
            ['displayString' => 'FromDate',               'key' => 'fromDate',          'value' => $collectionApplications->from_date],
            ['displayString' => 'ToDate',               'key' => 'toDate',          'value' => $collectionApplications->to_date],
        ]);
    }
    /**
     * return data fro card details 
     */
    public function getCardDetails($applicationDetails,)
    {
        // $ownerName = collect($ownerDetail)->map(function ($value) {
        //     return $value['owner_name'];
        // });
        // $ownerDetail = $ownerName->implode(',');
        $collectionApplications = collect($applicationDetails)->first();
        return new Collection([
            ['displayString' => 'Ward No.',             'key' => 'WardNo.',           'value' => $collectionApplications->ward_name],
            ['displayString' => 'zone Name.',             'key' => 'zoneName.',           'value' => $collectionApplications->zone_name],
            ['displayString' => 'Application No.',      'key' => 'ApplicationNo.',    'value' => $collectionApplications->application_no],
            ['displayString' => 'Rate',                  'key' => 'rate',              'value' => $collectionApplications->rate],


        ]);
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
                'applicationId' => "required",
                'status' => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $userId                 = authUser($req)->id;
            $mActiveRegistration    = new AgencyHoarding();
            $mWfRoleUsermap         = new WfRoleusermap();
            $currentDateTime        = Carbon::now();

            $application = $mActiveRegistration->getFullDetails($req)->firstOrFail();
            $workflowId = $application->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;
            if ($roleId != $application->current_role_id) {
                throw new Exception("You are not the Finisher!");
            }
            if ($application->doc_upload_status == false)
                throw new Exception("Document Not Fully Uploaded ");                                                                      // DA Condition
            if ($application->doc_verify_status == false)
                throw new Exception("Document Not Fully Verified!");

            # Change the concept 
            if ($req->status == 1) {
                $regNo = "AG/AMC-" . Carbon::now()->milli . Carbon::now()->diffInMicroseconds() . strtotime($currentDateTime);
                AgencyHoarding::where('id', $req->applicationId)
                    ->update([
                        "approve" => 1,
                        "registration_no" => $regNo
                    ]);
                return $returnData = [
                    "applicationId" => $application->application_no,
                    "registration_no" => $regNo
                ];
                return responseMsgs(true, ' register Application Approved!', $returnData);
            } else {
                AgencyHoarding::where('id', $req->applicationId)
                    ->update([
                        "approve" => 2,
                    ]);
                return responseMsgs(true, 'register Application Rejected!', $application->application_no);
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**
     * |get agency details via email
     */
    public function getAgencyDetails(Request $request)
    {
        try {
            $agencydetails = $this->_modelObj->getagencyDetails($request->auth['email']);           // get details via email of particular agency 
            if (!$agencydetails) {
                throw new Exception('You Have No Any Agency !!!');
            }
            remove_null($agencydetails);
            $data1['data'] = $agencydetails;
            $data1['total']= $agencydetails->count('hoarding_maters.hoardingId');

            return responseMsgs(true, "Agency Details", $data1, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**
      This function for get All Agency
     */
    public function getAllAgency(Request $req)
    {
        try {
            $agencydetails = $this->_modelObj->getaLLagency();
            return responseMsgs(true, "Agency Details", $agencydetails, "050502", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**
       get all hoarding address related ton agency
     */
    public function agencyhoardingAddress(Request $request)
    {
        try {
            $agencydetails = $this->_modelObj->agencyhoardingAddress($request->auth['email']);
            if (!$agencydetails) {
                throw new Exception('You Have No Any Agency !!!');
            }
            remove_null($agencydetails);
            $data1['data'] = $agencydetails;

            return responseMsgs(true, "Agency Details", $data1, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**
     * |---------------------------- Search Application ----------------------------|
     * | Search Application using provided condition For the Admin 
        | Serial No : 
     */
    public function searchHoarding(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'filterBy'  => 'required',
                'parameter' => 'required',
                'pages'     => 'nullable',
                'wardId'    => 'nullable',
                'zoneId'    => 'nullable'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mWaterConsumer = new AgencyMaster();
            $mHoardingMaster = new HoardingMaster();
            $mAgencyHoarding = new AgencyHoarding();
            $key            = $request->filterBy;
            $paramenter     = $request->parameter;
            $pages          = $request->perPage ? $request->perPage : 10;
            $string         = preg_replace("/([A-Z])/", "_$1", $key);
            $refstring      = strtolower($string);
            switch ($key) {
                case ("applicationNo"):                                                                        // Static
                    $ReturnDetails = $mAgencyHoarding->getByItsDetailsV2($request, $refstring, $paramenter)->paginate($pages);
                    $checkVal = collect($ReturnDetails)->last();
                    if (!$checkVal || $checkVal == 0)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                case ("mobileNo"):
                    $ReturnDetails = $mHoardingMaster->getByItsDetailsV2($request, $refstring, $paramenter)->paginate($pages);
                    $checkVal = collect($ReturnDetails)->last();
                    if (!$checkVal || $checkVal == 0)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                default:
                    throw new Exception("Data provided in filterBy is not valid!");
            }
            $list = [
                "current_page" => $ReturnDetails->currentPage(),
                "last_page" => $ReturnDetails->lastPage(),
                "data" => $ReturnDetails->items(),
                "total" => $ReturnDetails->total(),
            ];
            return responseMsgs(true, " Data According To Parameter!", remove_null($list), "", "01", "652 ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    /**
     this function for to get approve applications 

     */
    public function getApproveApplications(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "applicationId" => "required"
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $data =  $this->_agencyObj->getApproveDetails($request)->first();
            if (!$data) {
                throw new Exception("Application Not Found!");
            }
            return responseMsgs(true, " Data According To Parameter!", remove_null($data), "", "01", "652 ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}

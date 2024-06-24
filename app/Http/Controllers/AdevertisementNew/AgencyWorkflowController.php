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
use App\Models\AdvertisementNew\HoardingType;
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
use App\Models\AdvertisementNew\AgencyHoardingApproveApplication;
use App\Models\AdvertisementNew\HoardingRate;
use App\Models\AdvertisementNew\HoardType;
use App\Models\AdvertisementNew\TemporaryHoardingType;
use App\BLL\Advert;
use App\BLL\Advert\CalculateRate;
use App\Models\AdvertisementNew\AdHoardingAddress;
use App\Models\AdvertisementNew\MeasurementSize;
use App\Models\IdGenerationParam;
use App\Pipelines\Advertisement\SearchByApplicationNo;
use App\Pipelines\Advertisement\SearchByHoardingNo;
use App\Pipelines\Advertisement\SearchByMobileNo;
use Illuminate\Pipeline\Pipeline;

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
    protected $_mRefReqDocs;
    protected $_DocList;
    private $_refapplications;
    private $_documentLists;
    private $_tempId;
    protected $incrementStatus;
    // private static $registrationCounter = 1;

    public function __construct()
    {
        $this->_modelObj        = new AgencyMaster();
        $this->_hoarObj         = new HoardingMaster();
        $this->_brandObj        = new BrandMaster();
        $this->_advertObj       = new AdvertisementType();
        $this->_locatObj        = new Location();
        $this->_advObj          = new AdvertiserMaster();
        $this->_agencyObj       = new AgencyHoarding();
        $this->_activeHObj      = new AdvActiveHoarding();
        $this->_mRefReqDocs     = new RefRequiredDocument();
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
        // $this->_workflowIds = Config::get('workflow-constants.AGENCY_WORKFLOWS');
        $this->_moduleId        = Config::get('workflow-constants.ADVERTISMENT_MODULE');
        $this->_docCode         = Config::get('workflow-constants.HOARDING_DOC_CODE');
        $this->_tempParamId     = Config::get('workflow-constants.TEMP_AG_ID');
        $this->_tempId          = Config::get('advert.PARAM_ID.APPROVE');
        $this->_paramId         = Config::get('workflow-constants.AGY_ID');
        $this->_baseUrl         = Config::get('constants.BASE_URL');
        $this->_docUrl          = Config::get('workflow-constants.DOC_URL');
        $this->_fileUrl             = Config::get('workflow-constants.FILE_URL');
        $this->_userType            = Config::get("workflow-constants.REF_USER_TYPE");
        $this->_docReqCatagory      = Config::get("workflow-constants.DOC_REQ_CATAGORY");
        $this->_wfroles             = Config::get('workflow-constants.ROLE_LABEL');
        $this->_DocList = $this->_mRefReqDocs->getDocsByModuleId($this->_moduleId);
        // $this->Repository = $agency_repo;

        $this->_wfMasterId = Config::get('workflow-constants.AGENCY_WF_MASTER_ID');
        $this->incrementStatus  = true;
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

            return responseMsgs(true, "Successfully listed consumer req inbox details!", $inboxDetails, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $req->deviceId);
        }
    }

    # bta inbox
    public function btaInbox(Request $req)
    {
        try {
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $pages                  = $req->perPage ?? 10;
            $userId = authUser($req)->id;
            $ulbId = authUser($req)->ulb_id;
            $mDeviceId = $req->deviceId ?? "";

            $workflowRoles = $this->getRoleIdByUserId($userId);
            $roleId = $workflowRoles->map(function ($value) {                         // Get user Workflow Roles
                return $value->wf_role_id;
            });

            $refWard = $mWfWardUser->getWardsByUserId($userId);
            $wardId = $refWard->map(function ($value) {
                return $value->ward_id;
            });
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $agencyList = $this->getConsumerWfBaseQuerry($workflowIds, $ulbId)
                ->where('agency_hoardings.parked', true)
                ->orderByDesc('agency_hoardings.id')
                ->paginate($pages);

            $isDataExist = collect($agencyList)->last();
            if (!$isDataExist || $isDataExist == 0) {
                throw new Exception('Data not Found!');
            }
            return responseMsgs(true, "BTC Inbox List", remove_null($agencyList), "", 1.0, "560ms", "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010123, 1.0, "271ms", "POST", $mDeviceId);
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

    #==== out box=====
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
            return responseMsgs(true, "Successfully listed consumer req inbox details!", $inboxDetails, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * |---------------------------- Filter The Document For Viewing ----------------------------|
     * | @param documentList
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
     * |get doc to upload 
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
            $metaReqs['refTableDotId']      = 'agency_hoardngs.id';                                                // Static
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
            // if ($ifFullDocVerified == 1)
            //     throw new Exception("Document Fully Verified");

            DB::beginTransaction();
            if ($req->docStatus == "Verified") {
                $status = 1;
            }
            if ($req->docStatus == "Rejected") {
                # For Rejection Doc Upload Status and Verify Status will disabled 
                $status = 2;
                $applicationDtl->doc_upload_status = 0;
                $applicationDtl->doc_verify_status = false;
                $applicationDtl->save();
            }
            $reqs = [
                'remarks'           => $req->docRemarks,
                'verify_status'     => $status,
                'action_taken_by'   => $userId
            ];
            $mWfDocument->docVerifyRejectv2($wfDocId, $reqs);
            if ($req->docStatus == 'Verified')
                $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId, $req->docStatus);
            else
                $ifFullDocVerifiedV1 = 0;                                         // In Case of Rejection the Document Verification Status will always remain false

            // dd($ifFullDocVerifiedV1);
            if ($ifFullDocVerifiedV1 == 1) {                                     // If The Document Fully Verified Update Verify Status
                $applicationDtl->doc_verify_status = 1;
                $applicationDtl->save();
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
            'activeId' => $applicationId,
            'workflowId' => $refapplication->workflow_id,
            'moduleId' => 14
        ];
        $refDocList = $mWfActiveDocument->getVerifiedDocsByActiveId($refReq);
        return $this->isAllDocs($applicationId, $refDocList, $refapplication);
    }
    /**
     * | Checks the Document Upload Or Verify Status
     * | @param activeApplicationId
     * | @param refDocList list of Verified and Uploaded Documents
     * | @param refSafs saf Details
     */
    public function isAllDocs($applicationId, $refDocList, $refapp)
    {
        $docList = array();
        $verifiedDocList = array();
        $verifiedDocList['advDocs'] = $refDocList->where('owner_dtl_id', null)->values();
        $collectUploadDocList = collect();
        $advListDocs = $this->getadvTypeDocList($refapp);
        $docList['advDocs'] = explode('#', $advListDocs);
        collect($verifiedDocList['advDocs'])->map(function ($item) use ($collectUploadDocList) {
            return $collectUploadDocList->push($item['doc_code']);
        });
        $madvDocs = collect($docList['advDocs']);
        // List Documents
        $flag = 1;
        foreach ($madvDocs as $item) {
            if (!$item) {
                continue;
            }
            $explodeDocs = explode(',', $item);
            $type = $explodeDocs[0] ?? "O";
            array_shift($explodeDocs);
            foreach ($explodeDocs as $explodeDoc) {
                $changeStatus = 0;
                if (in_array($explodeDoc, $collectUploadDocList->toArray())) {
                    $changeStatus = 1;
                    break;
                }
            }
            // if ($changeStatus == 0 && $type == "R") {
            //     $flag = 0;
            //     break;
            // }
            if ($changeStatus == 0)
                break;
        }
        if ($flag == 0)
            return 0;
        else
            return 1;
    }

    #get doc which is required 

    public function getadvTypeDocList($refapps)
    {
        $this->_refapplications = $refapps;
        $moduleId = 14;

        $mrefRequiredDoc = RefRequiredDocument::firstWhere('module_id', $moduleId);
        if ($mrefRequiredDoc && isset($mrefRequiredDoc['requirements'])) {
            $this->_documentLists = $mrefRequiredDoc['requirements'];
        } else {
            $this->_documentLists = [];
        }
        return $this->_documentLists;
    }


    /**
     * | Check if the Document is Fully Verified or Not (0.1) | up
     * | @param
     * | @var 
     * | @return
        | Serial No :  
        | Working 
     */

    public function checkifFullDocVerified($applicationId)
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
        $ifDocUnverified = $refDocList->contains('verify_status', 0);
        if ($ifDocUnverified == true)
            return 0;
        else
            return 1;
    }

    /**
     *get all  applications details by id from workflow
     *working 
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

    /**
     * |------------------------------ Get Application details --------------------------------|
     * | @param request
     * | @var applicationDetails
     * | @var returnDetails
     * | @return returnDetails : list of individual applications
        | Workinig 
     */

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
        $roleDetails['roleDetails'] = collect($forwardBackward)['original']['data'];


        # Timeline Data
        $timelineData['timelineData'] = collect($request);

        # Departmental Post
        // $custom = $mCustomDetails->getCustomDetails($request);
        // $departmentPost['departmentalPost'] = collect($custom)->has('original') ? collect($forwardBackward)['original']['data'] : null;
        # Payments Details
        $returnValues = array_merge($aplictionList, $fullDetailsData, $levelComment, $timelineData, $roleDetails,);
        return responseMsgs(true, "listed Data!", remove_null($returnValues), "", "02", ".ms", "POST", "");
    }

    /**
     * function for return data of basic details
     */
    public function getBasicDetails($applicationDetails)
    {
        $collectionApplications = collect($applicationDetails)->first();
        return new Collection([
            ['displayString' => 'Agency Name',         'key' => 'agencyName',         'value' => $collectionApplications->agencyName],
            ['displayString' => 'Apply Date',       '   key' => 'applyDate',          'value' => Carbon::parse($collectionApplications->apply_date)->format('d-m-Y')],
            ['displayString' => 'From Date',           'key' => 'fromDate',           'value' => Carbon::parse($collectionApplications->from_date)->format('d/m/Y')],
            ['displayString' => 'To Date',             'key' => 'toDate',             'value' => Carbon::parse($collectionApplications->to_date)->format('d/m/Y')],
            ['displayString' => 'Advertiser',          'key' => 'advertiser',         'value' => $collectionApplications->advertiser],
            // ['displayString' => 'Hoarding Type',       'key' => 'hoardingType',       'value' => $collectionApplications->hoarding_type],
            ['displayString' => 'Advertisement Type',  'key' => 'advertisementType',  'value' => $collectionApplications->adv_type],
            ['displayString' => 'Application Type ',   'key' => 'applcationType',     'value' => $collectionApplications->application_type],
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
            ['displayString' => 'Apply-Date',       'key' => 'ApplyDate',          'value' => Carbon::parse($collectionApplications->apply_date)->format('d-m-Y')],
            ['displayString' => 'Ward No.',             'key' => 'WardNo.',           'value' => $collectionApplications->ward_name],
            ['displayString' => 'zone Name.',             'key' => 'zoneName.',           'value' => $collectionApplications->zone_name],
            ['displayString' => 'Application No.',      'key' => 'ApplicationNo.',    'value' => $collectionApplications->application_no],
            ['displayString' => 'Rate',                  'key' => 'rate',              'value' => $collectionApplications->rate],


        ]);
    }

    /**
     * | default final approval 
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
            DB::beginTransaction();
            $msg =  $this->finalApprovalRejection($req, $application);
            DB::commit();
            return responseMsgs(true, '', $msg);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    # function for approve or reject 

    public function finalApprovalRejection($req, $application)
    {

        $currentDateTime        = Carbon::now();
        $mAgencyApproval        = new AgencyHoardingApproveApplication();
        # check 
        $approveApplications = AgencyHoarding::query()
            ->where('id', $req->applicationId)
            ->first();
        # checking if applications  already exist 
        $checkExist = $mAgencyApproval->getApproveApplication($req->applicationId);
        if ($checkExist) {
            throw new Exception("Access Denied ! Consumer Already Exist!");
        }

        # handle status to approve or reject 
        if ($req->status == 1) {
            $idGeneration       = new PrefixIdGenerator($this->_tempId, $application->ulb_id);
            $registrationNo      = $idGeneration->getUniqueId();
            $registrationNo      = str_replace('/', '-', $registrationNo);
            $approveApplicationRep = $approveApplications
                ->update([
                    "approve" => 1,                                                                                    // approve                    
                    "registration_no" => $registrationNo,
                    "allotment_date"  => $currentDateTime
                ]);
            $returnData = [
                "applicationId" => $application->application_no,
                "registration_no" => $registrationNo
            ];
            $approveApplicationRep = $approveApplications->replicate();
            $approveApplicationRep->setTable('agency_hoarding_approve_applications');
            $approveApplicationRep->id = $approveApplications->id;
            $approveApplicationRep->save();
            return $msg  = "register Application Approved!";
            // return responseMsgs(true, 'register Application Approved!', $returnData);
        } else {
            AgencyHoarding::where('id', $req->applicationId)
                ->update([
                    "approve" => 2,
                ]);
            $approveApplicationRep = $approveApplications->replicate();
            $approveApplicationRep->setTable('agency_hoarding_rejected_applications');
            $approveApplicationRep->id = $approveApplications->id;
            $approveApplicationRep->save();
            return $msg  = "register Application Rejected!";

            // return responseMsgs(true, 'register Application Rejected!', $application->application_no);
        }
    }

    /**
     * Generate and return a registration number
     */

    private function generateRegistrationNumber()
    {
        $randomPart = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $registrationNumber = "AG/AMC-" . $randomPart;

        return $registrationNumber;
    }

    /*
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
            $data1['total'] = $agencydetails->count('hoarding_maters.hoardingId');

            return responseMsgs(true, "Agency Details", $data1, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
       |get all hoarding address related ton agency
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
     * |---------------------------- Search Hoarding Application ----------------------------|
     * | Search Application using provided condition 
     */

    public function searchHoarding(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'filterBy'  => 'nullable',
                'parameter' => 'nullable',
                'pages'     => 'nullable',
                'wardId'    => 'nullable',
                'zoneId'    => 'nullable'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $key            = $request->filterBy;
            $paramenter     = $request->parameter;
            $pages          = $request->perPage ? $request->perPage : 10;
            $string         = preg_replace("/([A-Z])/", "_$1", $key);
            $refstring      = strtolower($string);
            if ($key !== null) {
                switch ($key) {
                    case "applicationNo":
                        $data = $this->_agencyObj->getByItsDetailsV2($request, $refstring, $paramenter, $request->auth['email']);
                        if ($paramenter !== null) {
                            $data->where('agency_hoardings.' . $refstring, 'LIKE', '%' . $paramenter . '%');
                        }
                        $ReturnDetails = $data->paginate($pages);
                        // Check if data is not found
                        $checkVal = $ReturnDetails->count();
                        if (!$checkVal || $checkVal == 0) {
                            throw new Exception("Data according to " . $key . " not Found!");
                        }
                        break;
                    case ("mobile"):
                        $data = $this->_modelObj->getByItsDetailsV2($request, $refstring, $paramenter, $request->auth['email']);
                        if ($paramenter !== null) {
                            $data->where('agency_masters.' . $refstring, 'LIKE', '%' . $paramenter . '%');
                        }
                        $ReturnDetails = $data->paginate($pages);
                        // Check if data is not found
                        $checkVal = $ReturnDetails->count();
                        if (!$checkVal || $checkVal == 0) {
                            throw new Exception("Data according to " . $key . " not Found!");
                        }
                        break;
                    case ("hoardingNo"):
                        $data = $this->_modelObj->getByItsDetailsV2($request, $refstring, $paramenter, $request->auth['email']);
                        if ($paramenter !== null) {
                            $data->where('hoarding_masters.' . $refstring, 'LIKE', '%' . $paramenter . '%');
                        }
                        $ReturnDetails = $data->paginate($pages);
                        // Check if data is not found
                        $checkVal = $ReturnDetails->count();
                        if (!$checkVal || $checkVal == 0) {
                            throw new Exception("Data according to " . $key . " not Found!");
                        }
                        break;
                    default:
                        throw new Exception("Data provided in filterBy is not valid!");
                }
            } else {
                $ReturnDetails = $this->_agencyObj->getByItsDetailsV2($request, $refstring, $paramenter, $request->auth['email'])->paginate($pages);
                if (!$ReturnDetails) {
                    throw new Exception('data not found');
                }
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
     * | Get Apploication  which is approve by the
     * | @param request
     * | @var data
     */

    public function getApproveApplications(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            ["applicationId" => "required"]
        );

        if ($validated->fails()) {
            return validationError($validated);
        }

        try {

            $data = $this->_agencyObj->checkdtlsById($request->applicationId);
            if (!$data) {
                throw new Exception("Application Not Found!");
            }
            if ($data->payment_status == 0) {
                throw new Exception("Please Pay your Advertisement Amount ");
            }
            $fromDate = Carbon::parse($data->from_date);
            $toDate = Carbon::parse($data->to_date);

            #count number of days 
            $numberOfDays = $toDate->diffInDays($fromDate);

            #different advertisement type 
            $advertisementType = $data->adv_type;

            $query = $this->_agencyObj->getApproveDetails($request);                      // COMMON FUNCTION FOR ALL TYPE OF APPLICATION OF ADVERTISEMENT
            
            $mHoardingAddress           = new AdHoardingAddress();

            $getAddress = $mHoardingAddress->getAddress($request->applicationId)->get();

            if ($data->application_type == 'PERMANANT') {
                $query = $this->_agencyObj->getApproveDetails($request);
                $query->value = $query['measurement'];
                $query->key = 'SIZES';
            } else {
                switch ($advertisementType) {
                    case 'TEMPORARY_ADVERTISEMENT':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['size'];
                        $query->key = 'Size';
                        break;
                    case 'LAMP_POST':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['size'];
                        $query->key = 'Size';
                        break;
                    case 'ABOVE_KIOX_ADVERTISEMENT':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['size'];
                        $query->key = 'Size';
                        break;
                    case 'AD_POL':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['size'];
                        $query->key = 'Size';
                        break;
                    case 'COMPASS_CANTILEVER':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['size_square_feet'];
                        $query->key = 'Size';
                        break;
                    case 'GLOSSINE_BOARD':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['size_square_feet'];
                        $query->key = 'Size';
                        break;
                    case 'ADVERTISEMENT_ON_BALLONS':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['total_ballon'];
                        $query->key = 'Total Ballon';
                        break;
                    case 'ADVERTISEMENT_ON_THE_CITY_BUS':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['size_square_feet'];
                        $query->key = 'Size';

                        break;
                    case 'CITY_BUS_STOP':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['size_square_feet'];
                        $query->key = 'Size';
                        break;
                    case 'ADVERTISEMENT_ON_THE_WALL':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['size_square_feet'];
                        $query->key = 'Size';

                        break;
                    case 'ADVERTISEMENT_ON_MOVING_VEHICLE':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $query['total_vehicle'];
                        $query->key = 'Total Vehicle';
                        break;
                    case 'ROAD_SHOW_ADVERTISING':
                        $query = $this->_agencyObj->getApproveDetails($request);
                        $query->value = $numberOfDays;
                        $query->key = 'Total DAYS';
                        break;
                    default:
                        throw new Exception("Invalid Advertisement Type!");
                }
            }
            $query->total_nodays = $numberOfDays;
            $approveApplicationDetails["applicationDetails"] = $query;
            $approveApplicationDetails['address'] = $getAddress;
            return responseMsgs(true, "Data According To Parameter!", remove_null($approveApplicationDetails), "", "01", "652 ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |this function for approve applications 
     */

    public function getDetails($data)
    {
        $collectionApplications = collect($data)->first();
        $details = [
            ['displayString' => 'Agency Name',         'key' => 'agencyName',         'value' => $collectionApplications->agencyName],
            ['displayString' => 'Apply Date',          'key' => 'applyDate',          'value' => Carbon::parse($collectionApplications->apply_date)->format('d-m-Y')],
            ['displayString' => 'From Date',           'key' => 'fromDate',           'value' => Carbon::parse($collectionApplications->from_date)->format('d/m/Y')],
            ['displayString' => 'To Date',             'key' => 'toDate',             'value' => Carbon::parse($collectionApplications->to_date)->format('d/m/Y')],
            ['displayString' => 'Advertiser',          'key' => 'advertiser',         'value' => $collectionApplications->advertiser],
            ['displayString' => 'Advertisement Type',  'key' => 'advertisementType',  'value' => $collectionApplications->adv_type],
            ['displayString' => 'Application Type ',   'key' => 'applcationType',     'value' => $collectionApplications->application_type],
            ['displayString' => 'Address',             'key'  => 'address',           'value' => $collectionApplications->address],
            ['displayString' => 'Measurement Size ',   'key' => 'size',               'value' => $collectionApplications->sizes],
            ['displayString' => 'Total Ballon ',       'key' => 'totaBallon',         'value' => $collectionApplications->total_ballon],
            ['displayString' => 'Total vehicle',       'key' => 'totalVehicle',       'value' => $collectionApplications->total_vehicle],
            ['displayString' => 'Registration Number', 'key' => 'registrationNo',     'value' => $collectionApplications->registration_no],
            ['displayString' => 'Purpose',             'key' => 'purpose',             'value' => $collectionApplications->purpose],
        ];
        return (object)$details;;
    }

    /**
       | get dashboard data of agency 
       | raw query 
       | sr no .=1
     */

    public function dashboardAgency(Request $request)
    {
        try {
            $email = $request->auth['email'];
            $data = DB::selectOne("
                SELECT
                agency_masters.agency_name,
                agency_masters.mobile,
                    COUNT(CASE WHEN approve = 0 THEN 1 ELSE NULL END) AS pendings,
                    COUNT(CASE WHEN approve = 1 THEN 1 ELSE NULL END) AS approved,
                    COUNT(CASE WHEN approve = 2 THEN 1 ELSE NULL END) AS rejected
                FROM
                agency_masters
                LEFT    JOIN agency_hoardings ON agency_hoardings.agency_id = agency_masters.id
                WHERE
                    agency_masters.email = :email
                    AND agency_masters.status = 1
                    group by 
                    agency_masters.agency_name,
                    agency_masters.mobile
            ", ['email' => $email]);
            return responseMsgs(true, "Agency Details", $data, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * details of agency aplications by email 
     */

    public function getAgencyAplicationdtl(Request $request)
    {
        try {
            $hoardingType = $request->hoardingType;
            if (!is_null($hoardingType)) {
                $agencydetails = $this->_agencyObj->getApplicationDetails($request->auth['email']);
            } else {
                $agencydetails = $this->_agencyObj->getApplicationDtl($request->auth['email']);
            }
            if (!$agencydetails) {
                throw new Exception('Agency details not found!');
            }
            return responseMsgs(true, "Agency Application Details", $agencydetails, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * get hoarding type master
     */

    public function hoardingType(Request $request)
    {
        try {
            $mHoardType = new HoardingType();
            $details = $mHoardType->getHoardingType();
            if (!$details) {
                throw new Exception('agency details not found!');
            }
            return responseMsgs(true, "Hoarding Type", $details, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * ulb dashboard
     */

    public function ulbDashboard(Request $request)
    {
        $todate = Carbon::now();
        try {
            $data = DB::select("
            WITH filtered_hoardings AS (
                SELECT
                  ah.id,
                  ah.application_no,
                  ah.rate,
                  ah.from_date,
                  ah.to_date,
                  ah.allotment_date,
                  am.agency_name,
                  ht.type as hoarding_type,
                  hm.hoarding_no,
                  hm.address,
                  ah.approve
                FROM
                  agency_hoardings AS ah
                  JOIN agency_masters AS am ON am.id = ah.agency_id
                  JOIN hoarding_masters AS hm ON hm.id = ah.hoarding_id
                  JOIN hoarding_types as ht on ht.id=hm.hoarding_type_id
                WHERE
                  ah.status = true
              )
              SELECT
                id,
                application_no,
                rate,
                from_date,
                to_date,
                allotment_date,
                agency_name,
                hoarding_type,
                hoarding_no,
                address,
                CASE 
                  WHEN approve = 0 THEN 'Pending'
                  WHEN approve = 1 THEN 'Approved'
                  WHEN approve = 2 THEN 'Rejected'
                  ELSE 'Unknown Status'
                END AS approval_status,
                approve 
              FROM
                filtered_hoardings
              ORDER BY
                id");
            $totalApplications = count($data);
            $pendingCount = count(array_filter($data, function ($item) {
                return $item->approve == 0;
            }));

            $approvedCount = count(array_filter($data, function ($item) {
                return $item->approve == 1;
            }));

            $rejectedCount = count(array_filter($data, function ($item) {
                return $item->approve == 2;
            }));
            $result = [
                'data' => array_values($data),
                'pendingCount' => $pendingCount,
                'approvedCount' => $approvedCount,
                'rejectedCount' => $rejectedCount,
                'totalApplications' => $totalApplications,
                'date' => $todate->format('d/m/Y'),
            ];
            return responseMsgs(true, "Agency Details", $result, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * VALIDATE HOARDING  DETAILS BY ID 
     */

    public function getHoardingDtlsById(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'id' => "required",
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $hoardingId = $req->id;
            $data = $this->_hoarObj->gethoardingDetailbyId($hoardingId);
            $data['aresSqft'] = $data->length * $data->width;
            return responseMsgs(true, "Agency Details", $data, "050502", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | send back to citizen
     * |
     */

    public function backToCitizen(Request $req)
    {

        $validated = Validator::make(
            $req->all(),
            [
                'applicationId' => "required",
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            // Variable initialization
            $redis = Redis::connection();
            $mAgencyHoarding = AgencyHoarding::find($req->applicationId);
            if ($mAgencyHoarding->doc_verify_status == 1)
                throw new Exception("All Documents Are varified, So Application is Not BTC !!!");
            if ($mAgencyHoarding->doc_upload_status == 1)
                throw new Exception("No Any Document Rejected, So Application is Not BTC !!!");
            $workflowId = $mAgencyHoarding->workflow_id;
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }

            $mAgencyHoarding->current_role_id = $backId->wf_role_id;
            $mAgencyHoarding->parked = 1;
            $mAgencyHoarding->save();

            $metaReqs['moduleId'] = $this->_moduleId;
            $metaReqs['workflowId'] = $mAgencyHoarding->workflow_id;
            $metaReqs['refTableDotId'] = "agency_hoardings.id";
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['verificationStatus'] = $req->verificationStatus;
            $metaReqs['senderRoleId'] = $req->currentRoleId;
            $req->request->add($metaReqs);

            $req->request->add($metaReqs);
            $track = new WorkflowTrack();
            $track->saveTrack($req);

            return responseMsgs(true, "Successfully Done", "", "", '050131', '01', responseTime(), 'POST', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050131", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Reuploaded rejected document
     */

    public function reuploadDocument(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'docId' => 'required|digits_between:1,9223372036854775807',
            'image' => 'required|mimes:png,jpeg,pdf,jpg'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $magencyHoard = new AgencyHoarding();
            DB::beginTransaction();
            $appId = $magencyHoard->reuploadDocument($req);
            $this->checkFullUpload($appId);
            DB::commit();
            return responseMsgs(true, "Document Uploaded Successfully", "", "050133", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, "Document Not Uploaded", "", "050133", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Cheque full upload document or not
     * |  Function - 35
     */

    public function checkFullUpload($applicationId)
    {
        $docCode = $this->_docCode;
        $mWfActiveDocument = new WfActiveDocument();
        $mRefRequirement = new RefRequiredDocument();
        $moduleId = $this->_moduleId;
        $totalRequireDocs = $mRefRequirement->totalNoOfDocs($moduleId);
        $appDetails = AgencyHoarding::find($applicationId);
        $totalUploadedDocs = $mWfActiveDocument->totalUploadedDocs($applicationId, $appDetails->workflow_id, $moduleId);
        if ($totalRequireDocs == $totalUploadedDocs) {
            $appDetails->doc_upload_status = true;
            $appDetails->doc_verify_status = '0';
            $appDetails->parked = false;
            $appDetails->save();
        } else {
            $appDetails->doc_upload_status = '0';
            $appDetails->doc_verify_status = '0';
            $appDetails->save();
        }
    }

    /**
     * |get rejected doument via agency 
     */

    public function getRjectedDoc(Request $request)
    {
        try {
            $pages                  = $request->perPage ?? 10;
            $workflowId = 203;                                                                                      //static
            $email = ($request->auth['email']);
            $agencydetails = $this->_agencyObj->getRejectDocs($request->auth['email'], $workflowId)->paginate($pages);;
            if (!$agencydetails) {
                throw new Exception('data not found ');
            }

            return responseMsgs(true, "Rejected Documents", $agencydetails, "050133", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, "Rejected Document Not Found", "", "050133", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * |get rejected doument via agency 
     */

    public function getRjectedDocById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|digits_between:1,9223372036854775807',

        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $applicationId = $request->id;
            $workflowId = 203;                                                                                      //static
            $email = ($request->auth['email']);
            $agencydetails = $this->_agencyObj->getRejectDocbyId($request->auth['email'], $workflowId, $applicationId);
            $agencydetails = collect($agencydetails)->keyBy('doc_code');
            if (!$agencydetails) {
                throw new Exception('data not found ');
            }
            return responseMsgs(true, "Rejected Documents", $agencydetails, "050133", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, "Document Not Uploaded", "", "050133", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * get hoarding type master
     */

    public function hoardType(Request $request)
    {
        try {
            $mHoardType = new TemporaryHoardingType();
            $details = $mHoardType->gethoardType();
            if (!$details) {
                throw new Exception('agency details not found!');
            }
            return responseMsgs(true, "Hoarding Type", $details, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    #get rate by dates 

    public function getRateByDate(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'   => 'required',
            'from' => 'required',
            "to"  =>  'required'

        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $mHoardRate = new HoardingRate();
            $fromDate = Carbon::parse($req->from);
            $toDate = Carbon::parse($req->to);
            $applicationId = $req->id;
            $numberOfDays = $toDate->diffInDays($fromDate);

            $getRate    = $mHoardRate->getHoardSizerate($applicationId);
            if (!$getRate) {
                throw new exception('size not found!');
            }
            $rate       = $getRate->per_day_rate;
            $totalamount = $rate * $numberOfDays;
            $data['rate'] = $totalamount;
            return  responseMsgs(true, "Rate", $data, "050133", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    #get size of temporary advertisment

    public function getSizeAdvertisement(Request $request)
    {
        try {
            $advertisementType = $request->advertisementType;
            $mHoardSize = new HoardingRate();
            if ($request->applicationType == 'TEMPORARY') {
                $details = $mHoardSize->getHoardingSize($advertisementType);
            }
            if (!$details) {
                throw new Exception('Size Of Hoardings not found');
            }
            return responseMsgs(true, "Hoarding Type", $details, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * |get All measurement for Permanant advertiement 
     */

    public function getAllFixedMeasurementPermanantAdv(Request $request)
    {
        try {
            $mPemanantAdvSize = new MeasurementSize();
            $details = $mPemanantAdvSize->getAllMeasurement();
            if (!$details) {
                throw new Exception('data not found');
            }
            return responseMsgs(true, "Hoarding Size", $details, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * |calculate rate of hoarding advertisemnet
     * | As per square feet or Days/Month
     */

    public function calculateRate(Request $req)
    {
        $validator = Validator::make($req->all(), [

            'propertyId'        => 'nullable',
            'from'              => 'nullable|date',
            'to'                => 'nullable|date',
            'applicationType'   => 'nullable|string|in:PERMANANT,TEMPORARY',
            'advertisementType' => 'nullable|string',
            'squareFeetId'      => 'nullable',
            'squarefeet'        => 'nullable',
            'Noofhoardings'     => 'nullable'

        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $mAdvCalculateRate = new CalculateRate();
            $rate = $mAdvCalculateRate->calculateRateDtls($req);
            return responseMsgs(true, "DATA", $rate, "050502", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    public function searchHoardingPipeline(Request $request)
    {
        // Define validation rules
        $validated = Validator::make($request->all(), [
            'applicationNo'  => 'nullable',
            'hoardingNo'     => 'nullable',
            'pages'     => 'nullable',
            'wardId'    => 'nullable',
            'zoneId'    => 'nullable'
        ]);

        // Handle validation errors
        if ($validated->fails()) {
            return $this->validationError($validated);
        }
        try {
            $refNo = 0;
            $key = $request->applicationNo;
            // Create a pipeline to process the search
            $result = $this->_agencyObj->getByItsDetailsV2($request, $key, $refNo, $request->auth['email']);
            // $result = HoardingMaster::where('status',1);
            // $mobile =  $this->_modelObj->getByItsDetailsV2($request, $key, $refNo, $request->auth['email']);
            $result = app(Pipeline::class)
                ->send($result)
                ->through([
                    SearchByApplicationNo::class,
                    SearchByMobileNo::class,
                    SearchByHoardingNo::class
                ])
                ->thenReturn()
                ->get();
            return responseMsgs(true, "Data According To Parameter!", remove_null($result), "", "01", "652 ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Handle validation errors method
    private function validationError($validated)
    {
        return responseMsgs(false, $validated->errors()->first(), "", "01", "1.0", "", "POST", "");
    }
    #get Temporary vehcle list
    public function getVehicle(Request $request)
    {
        try {
            $mTemporaryHoardingType = new TemporaryHoardingType();
            $getVehicle = $mTemporaryHoardingType->gethoardType();
            return responseMsgs(true, "Data According To Parameter!", remove_null($getVehicle), "", "01", "652 ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}

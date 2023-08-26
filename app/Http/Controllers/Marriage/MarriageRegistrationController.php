<?php

namespace App\Http\Controllers\Marriage;

use App\BLL\Marriage\CalculatePenalty;
use App\Http\Controllers\Controller;
use App\MicroServices\DocumentUpload;
use App\MicroServices\IdGeneration;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Advertisements\RefRequiredDocument;
use App\Models\Advertisements\WfActiveDocument;
use App\Models\Marriage\MarriageActiveRegistration;
use App\Models\Marriage\MarriageApprovedRegistration;
use App\Models\Marriage\MarriageRazorpayRequest;
use App\Models\Marriage\MarriageRazorpayResponse;
use App\Models\Marriage\MarriageTransaction;
use App\Models\UlbMaster;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\Workflows\WorkflowTrack;
use App\Pipelines\Marriage\SearchByApplicationNo;
use App\Pipelines\Marriage\SearchByName;
use App\Traits\Marriage\MarriageTrait;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class MarriageRegistrationController extends Controller
{

    use Workflow;
    use MarriageTrait;

    private $_workflowMasterId;
    private $_marriageParamId;
    private $_marriageModuleId;
    private $_userType;
    private $_marriageWfRoles;
    private $_relativePath;
    private $_applicationType;
    private $_registrarRoleId;
    private $_paymentUrl;
    # Class constructer 
    public function __construct()
    {
        $this->_marriageModuleId    = Config::get('marriage.MODULE_ID');
        $this->_workflowMasterId    = Config::get("marriage.WORKFLOW_MASTER_ID");
        $this->_marriageParamId     = Config::get("marriage.PARAM_ID");
        $this->_userType            = Config::get("marriage.REF_USER_TYPE");
        $this->_registrarRoleId     = Config::get("marriage.REGISTRAR_ROLE_ID");
        $this->_relativePath        = Config::get("marriage.RELATIVE_PATH");
        $this->_applicationType     = Config::get("marriage.APPLICATION_TYPE");
        $this->_paymentUrl          = Config::get('constants.PAYMENT_URL');
    }

    /**
     * | Apply for marriage registration
     */
    public function apply(Request $req)
    {
        try {
            $mWfWorkflow = new WfWorkflow();
            $mWfRoleusermaps  = new WfRoleusermap();
            $mMarriageActiveRegistration = new MarriageActiveRegistration();
            $mMarriageTransaction = new MarriageTransaction();
            $idGeneration = new IdGeneration;
            $user                       = authUser($req);
            $ulbId                      = $user->ulb_id ?? $req->ulbId;
            $userType                   = $user->user_type;
            $workflowMasterId           = $this->_workflowMasterId;
            $marriageParamId            = $this->_marriageParamId;
            $registrarRoleId            = $this->_registrarRoleId;

            # Get initiator and finisher for the workflow 
            $ulbWorkflowId = $mWfWorkflow->getulbWorkflowId($workflowMasterId, $ulbId);
            if (!$ulbWorkflowId) {
                throw new Exception("Respective Ulb is not maped to 'marriage Registration' Workflow!");
            }
            $registrationCharges = 100;
            $refInitiatorRoleId  = $this->getInitiatorId($ulbWorkflowId->id);
            $refFinisherRoleId   = $this->getFinisherId($ulbWorkflowId->id);
            $mreqs = [
                "roleId" => $registrarRoleId,
                "ulbId"  => $ulbId
            ];
            $registrarId         = $mWfRoleusermaps->getUserId($mreqs);
            $finisherRoleId      = collect(DB::select($refFinisherRoleId))->first();
            $initiatorRoleId     = collect(DB::select($refInitiatorRoleId))->first();
            $userId              = $user->id;
            $citizenId           = null;
            if ($userType == 'Citizen') {
                $initiatorRoleId = collect($initiatorRoleId)['forward_role_id'];        // Send to DA in Case of Citizen
                $userId = null;
                $citizenId = $user->id;
            } else
                $initiatorRoleId = collect($initiatorRoleId)['role_id'];                // Send to BO in Case of JSK

            #_Check BPL for Payment Amount
            if ($req->bpl == true) {
                $paymentAmount = 0;
                $penaltyAmount = 0;
            } else {
                $paymentAmount = 50;
                $calculatePenalty = new CalculatePenalty;
                $penaltyAmount = $calculatePenalty->calculate($req);
            }

            $prefixIdGeneration = new PrefixIdGenerator($marriageParamId, $ulbId);
            $marriageApplicationNo = $prefixIdGeneration->generate();
            $reqs = $this->makeRequest($req);
            $refData = [
                "finisher_role_id"  => collect($finisherRoleId)['role_id'],
                "initiator_role_id" => $initiatorRoleId,
                "current_role"      => $initiatorRoleId,
                "workflow_id"       => $ulbWorkflowId->id,
                "application_no"    => $marriageApplicationNo,
                "user_id"           => $userId,
                "citizen_id"        => $citizenId,
                "registrar_id"      => $registrarId->user_id,
                "payment_amount"    => $paymentAmount,
                "penalty_amount"    => $penaltyAmount,
                "ulb_id"            => $ulbId
            ];
            $newReqs =  array_merge($reqs, $refData);
            DB::beginTransaction();
            $applicationDetails = $mMarriageActiveRegistration->saveRegistration($newReqs);


            if ($req->bpl == true) {
                $tranNo = $idGeneration->generateTransactionNo($ulbId);
                $transanctionReqs = [
                    "application_id" => $applicationDetails['id'],
                    "tran_date"      => Carbon::now(),
                    "tran_no"        => $tranNo,
                    "payment_mode"   => "By " . $userType,
                    "amount_paid"    => 0,
                    "amount"         => 0,
                    "penalty_amount" => 0,
                    "workflow_id"    => $applicationDetails['workflow_id'],
                    "ulb_id"         => $applicationDetails['ulb_id'],
                    "user_id"        => $userId,
                    "citizen_id"     => $citizenId,
                    "status"         => 1,
                ];
                $tranDtl = $mMarriageTransaction->store($transanctionReqs);
            }
            DB::commit();
            $returnData = [
                "id" => $applicationDetails['id'],
                "applicationNo" => $applicationDetails['application_no'],
            ];
            return responseMsgs(true, "Marriage Registration Application Submitted!", $returnData, "100101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "100101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Get Doc List
     */
    public function getDocList(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $mMarriageActiveRegistration = new MarriageActiveRegistration();
            $applicationId               = $req->applicationId;

            $refMarriageApplication = MarriageActiveRegistration::find($applicationId);                      // Get Marriage Details
            if (is_null($refMarriageApplication)) {
                throw new Exception("Application Not Found for respective ($applicationId) id!");
            }

            $filterDocs = $this->getMarriageDocLists($refMarriageApplication);
            if (!empty($filterDocs))
                $totalDocLists['listDocs'] = $this->filterDocument($filterDocs, $refMarriageApplication);                                     // function(1.2)
            else
                $totalDocLists['listDocs'] = [];
            // $totalDocLists = collect($document);
            // $totalDocLists['docUploadStatus']   = $refMarriageApplication->doc_upload_status;
            // $totalDocLists['docVerifyStatus']   = $refMarriageApplication->doc_verify_status;
            // $totalDocLists['ApplicationNo']     = $refMarriageApplication->application_no;
            return responseMsgs(true, "", remove_null($totalDocLists), "100102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Filtering
     */
    public function filterDocument($documentList, $refSafs, $witnessId = null)
    {
        $mWfActiveDocument = new WfActiveDocument();

        $safId = $refSafs->id;
        $workflowId = $refSafs->workflow_id;
        $moduleId = $this->_marriageModuleId;
        $uploadedDocs = $mWfActiveDocument->getDocByRefIds($safId, $workflowId, $moduleId);
        $explodeDocs = collect(explode('#', $documentList));

        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs, $witnessId, $refSafs) {
            $document = explode(',', $explodeDoc);
            $key = array_shift($document);
            $label = array_shift($document);
            $documents = collect();

            collect($document)->map(function ($item) use ($uploadedDocs, $documents, $witnessId, $refSafs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $item)
                    // ->where('Witness_dtl_id', $witnessId)
                    ->first();

                if ($uploadedDoc) {
                    $response = [
                        "uploadedDocId" => $uploadedDoc->id ?? "",
                        "documentCode" => $item,
                        // "WitnessId" => $uploadedDoc->Witness_dtl_id ?? "",
                        "docPath" => $uploadedDoc->doc_path ?? "",
                        "verifyStatus" => $refSafs->payment_status == 1 ? ($uploadedDoc->verify_status ?? "") : 0,
                        "remarks" => $uploadedDoc->remarks ?? "",
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType'] = $key;
            $reqDoc['docName'] = substr($label, 1, -1);

            // Check back to citizen status
            $uploadedDocument = $documents->sortByDesc('uploadedDocId')->first();                           // Get Last Uploaded Document

            if (collect($uploadedDocument)->isNotEmpty() && $uploadedDocument['verifyStatus'] == 2) {
                $reqDoc['btcStatus'] = true;
            } else
                $reqDoc['btcStatus'] = false;
            $reqDoc['uploadedDoc'] = $documents->sortByDesc('uploadedDocId')->first();                      // Get Last Uploaded Document

            $reqDoc['masters'] = collect($document)->map(function ($doc) use ($uploadedDocs, $refSafs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                $arr = [
                    "documentCode" => $doc,
                    "docVal" => ucwords($strReplace),
                    "uploadedDoc" => $uploadedDoc->doc_path ?? "",
                    "uploadedDocId" => $uploadedDoc->id ?? "",
                    "verifyStatus'" => $refSafs->payment_status == 1 ? ($uploadedDoc->verify_status ?? "") : 0,
                    "remarks" => $uploadedDoc->remarks ?? "",
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return $filteredDocs;
    }

    /**
     * | Get Doc List
     */
    public function getMarriageDocLists($refApplication)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $moduleId = $this->_marriageModuleId;
        $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "MARRIAGE_REQUIRED_DOC")->requirements;

        if ($refApplication->is_bpl == true) {
            $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "BPL_CATEGORY")->requirements;
        }

        //GROOM PASSPORT
        if ($refApplication->groom_nationality == 'NRI') {
            $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "GROOM_PASSPORT")->requirements;
        }
        //BRIDE PASSPORT
        if ($refApplication->bride_nationality == 'NRI') {
            $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "BRIDE_PASSPORT")->requirements;
        }
        return $documentList;
    }

    /**
     * | Doc Upload
     */
    public function uploadDocument(Request $req)
    {
        $req->validate([
            "applicationId" => "required|numeric",
            "document" => "required|mimes:pdf,jpeg,png,jpg",
            "docCode" => "required"
        ]);
        $extention = $req->document->getClientOriginalExtension();
        $req->validate([
            'document' => $extention == 'pdf' ? 'max:10240' : 'max:1024',
        ]);

        try {
            $user = collect(authUser($req));

            $metaReqs = array();
            $docUpload = new DocumentUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mMarriageActiveRegistration = new MarriageActiveRegistration();
            $relativePath = $this->_relativePath;
            $marriageRegitrationDtl = MarriageActiveRegistration::find($req->applicationId);
            if (!$marriageRegitrationDtl)
                throw new Exception("Application Not Found");
            $refImageName = $req->docCode;
            $refImageName = $marriageRegitrationDtl->id . '-' . $refImageName;
            $document = $req->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs['moduleId']     = $this->_marriageModuleId;
            $metaReqs['activeId']     = $marriageRegitrationDtl->id;
            $metaReqs['workflowId']   = $marriageRegitrationDtl->workflow_id;
            $metaReqs['ulbId']        = $marriageRegitrationDtl->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document']     = $imageName;
            $metaReqs['docCode']      = $req->docCode;

            $metaReqs = new Request($metaReqs);
            $mWfActiveDocument->postDocuments($metaReqs, $user);

            $docUploadStatus = $this->checkFullDocUpload($req->applicationId);
            if ($docUploadStatus == 1) {                                        // Doc Upload Status Update
                $marriageRegitrationDtl->doc_upload_status = 1;
                if ($marriageRegitrationDtl->parked == true)                                // Case of Back to Citizen
                    $marriageRegitrationDtl->parked = false;

                if ($marriageRegitrationDtl->is_bpl == true)
                    $marriageRegitrationDtl->payment_status = true;

                $marriageRegitrationDtl->save();
            }
            return responseMsgs(true, "Document Uploadation Successful", "", "100103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Check Full Upload Doc Status
     */
    public function checkFullDocUpload($applicationId)
    {
        $mMarriageActiveRegistration = new MarriageActiveRegistration();
        $mWfActiveDocument = new WfActiveDocument();
        $marriageRegitrationDtl = MarriageActiveRegistration::find($applicationId);
        // Get Saf Details
        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $marriageRegitrationDtl->workflow_id,
            'moduleId' => $this->_marriageModuleId
        ];
        $req = new Request($refReq);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        return $this->isAllDocs($applicationId, $refDocList, $marriageRegitrationDtl);
    }

    public function isAllDocs($applicationId, $refDocList, $marriageRegitrationDtl)
    {
        $docList = array();
        $verifiedDocList = array();
        // $mSafsOwners = new PropActiveSafsOwner();
        // $refSafOwners = $mSafsOwners->getOwnersBySafId($applicationId);
        $marriageDocs = $this->getMarriageDocLists($marriageRegitrationDtl);
        $docList['marriageDocs'] = explode('#', $marriageDocs);

        $verifiedDocList['marriageDocs'] = $refDocList->where('owner_dtl_id', null)->values();
        $collectUploadDocList = collect();
        collect($verifiedDocList['marriageDocs'])->map(function ($item) use ($collectUploadDocList) {
            return $collectUploadDocList->push($item['doc_code']);
        });

        // $marriageDocs = collect();
        // Property List Documents
        $flag = 1;
        foreach ($docList['marriageDocs'] as $item) {
            $explodeDocs = explode(',', $item);
            array_shift($explodeDocs);
            foreach ($explodeDocs as $explodeDoc) {
                $changeStatus = 0;
                if (in_array($explodeDoc, $collectUploadDocList->toArray())) {
                    $changeStatus = 1;
                    break;
                }
            }
            if ($changeStatus == 0) {
                $flag = 0;
                break;
            }
        }

        if ($flag == 0)
            return 0;
        else
            return 1;
    }

    /**
     *  | Get uploaded documents
     */
    public function getUploadedDocuments(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mMarriageActiveRegistration = new MarriageActiveRegistration();
            $moduleId = $this->_marriageModuleId;

            $marriageDetails = MarriageActiveRegistration::find($req->applicationId);
            if (!$marriageDetails)
                throw new Exception("Application Not Found for this application Id");

            $workflowId = $marriageDetails->workflow_id;
            $documents = $mWfActiveDocument->getDocsByAppId($req->applicationId, $workflowId, $moduleId);
            return responseMsgs(true, "Uploaded Documents", remove_null($documents), "100104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "100104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Registrar Inbox
     */
    public function inbox(Request $req)
    {
        try {
            $user = authUser($req);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $perPage = $req->perPage ?? 10;

            $roleId = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $list = MarriageActiveRegistration::whereIn('workflow_id', $workflowIds)
                ->where('marriage_active_registrations.ulb_id', $ulbId)
                ->whereIn('marriage_active_registrations.current_role', $roleId)
                ->orderByDesc('marriage_active_registrations.id');

            $inbox = app(Pipeline::class)
                ->send(
                    $list
                )
                ->through([
                    SearchByApplicationNo::class,
                    SearchByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "", remove_null($inbox), "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Get details by id
     */
    public function details(Request $req)
    {
        $req->validate([
            'applicationId' => 'required'
        ]);

        try {
            $details = array();
            $mMarriageActiveRegistration = new MarriageActiveRegistration();
            // $mWorkflowTracks = new WorkflowTrack();
            // $mCustomDetails = new CustomDetail();
            // $mForwardBackward = new WorkflowMap();
            $details = MarriageActiveRegistration::find($req->applicationId);
            if (!$details)
                throw new Exception("Application Not Found");
            $witnessDetails = array();

            for ($i = 0; $i < 3; $i++) {
                $index = $i + 1;
                $name = "witness$index" . "_name";
                $mobile = "witness$index" . "_mobile_no";
                $address = "witness$index" . "_residential_address";
                $witnessDetails[$i]['withnessName'] = $details->$name;
                $witnessDetails[$i]['withnessMobile'] = $details->$mobile;
                $witnessDetails[$i]['withnessAddress'] = $details->$address;
            }
            if (!$details)
                throw new Exception("Application Not Found for this id");

            // Data Array
            $marriageDetails = $this->generateMarriageDetails($details);         // (Marriage Details) Trait function to get Marriage Details
            $marriageElement = [
                'headerTitle' => "Marriage Details",
                "data" => $marriageDetails
            ];

            $brideDetails = $this->generateBrideDetails($details);   // (Property Details) Trait function to get Property Details
            $brideElement = [
                'headerTitle' => "Bride Details",
                'data' => $brideDetails
            ];

            $groomDetails = $this->generateGroomDetails($details);   // (Property Details) Trait function to get Property Details
            $groomElement = [
                'headerTitle' => "Groom Details",
                'data' => $groomDetails
            ];

            $groomElement = [
                'headerTitle' => "Groom Details",
                'data' => $groomDetails
            ];

            // $fullDetailsData->application_no = $details->application_no;
            $fullDetailsData['application_no'] = $details->application_no;
            $fullDetailsData['apply_date'] = $details->created_at->format('d-m-Y');
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$marriageElement, $brideElement, $groomElement]);

            $witnessDetails = $this->generateWitnessDetails($witnessDetails);   // (Property Details) Trait function to get Property Details

            // Table Array
            $witnessElement = [
                'headerTitle' => 'Witness Details',
                'tableHead' => ["#", "Witness Name", "Witness Mobile No", "Address"],
                'tableData' => $witnessDetails
            ];

            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$witnessElement]);
            // Card Details
            $cardElement = $this->generateCardDtls($details);
            $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

            // $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $req->applicationId);
            // $fullDetailsData['levelComment'] = $levelComment;

            // $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $req->applicationId, $details->user_id);
            // $fullDetailsData['citizenComment'] = $citizenComment;

            $metaReqs['customFor'] = 'MARRIAGE';
            $metaReqs['wfRoleId'] = $details->current_role;
            $metaReqs['workflowId'] = $details->workflow_id;
            $metaReqs['lastRoleId'] = $details->last_role_id;
            $req->request->add($metaReqs);

            // $forwardBackward = $mForwardBackward->getRoleDetails($req);
            // $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData['timelineData'] = collect($req);

            // $custom = $mCustomDetails->getCustomDetails($req);
            // $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            return responseMsgs(true, "Marriage Details", $fullDetailsData, "100108", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100108", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Static Details
     */
    public function staticDetails(Request $req)
    {
        $req->validate([
            "applicationId" => "required|numeric"
        ]);

        try {
            $registrationDtl = MarriageActiveRegistration::find($req->applicationId);
            $transactions = new MarriageTransaction();
            $tranNo = null;
            if (!$registrationDtl)
                throw new Exception('No Data Found');

            $tranDtl = $transactions->where('application_id', $req->applicationId)
                ->orderbydesc('id')
                ->first();

            if ($tranDtl)
                $tranNo = $tranDtl->tran_no;

            if (isset($registrationDtl->appointment_date))
                $registrationDtl->appointment_status = true;
            else
                $registrationDtl->appointment_status = false;
            $registrationDtl->total_payable_amount = $registrationDtl->payment_amount + $registrationDtl->penalty_amount;
            $registrationDtl->tran_no = $tranNo;

            return responseMsgs(true, "", remove_null($registrationDtl), "100105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | List Applications
     */
    public function listApplications(Request $req)
    {
        try {
            $registrationDtl = MarriageActiveRegistration::where('citizen_id', authUser($req)->id)->get();
            if (!$registrationDtl)
                throw new Exception('No Data Found');

            return responseMsgs(true, "", remove_null($registrationDtl), "100106", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100106", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Fix Appointment Date
     */
    public function appointmentDate(Request $req)
    {
        $req->validate([
            "applicationId" => "required|numeric"
        ]);

        try {
            $registrationDtl = MarriageActiveRegistration::find($req->applicationId);
            if (!$registrationDtl)
                throw new Exception('Application Not Found');

            if ($registrationDtl->doc_upload_status == 0)
                throw new Exception('Full Document Not Uploaded');

            if ($registrationDtl->doc_verify_status == 0)
                throw new Exception('Full Document Not Verified');

            if (!is_null($registrationDtl->appointment_date))
                throw new Exception('Appointment Date Is Already Set On ' . $registrationDtl->appointment_date);

            $registrationDtl->appointment_date = Carbon::now()->addMonth(1);
            $registrationDtl->save();

            return responseMsgs(true, "Appointment Date is Fixed on " . $registrationDtl->appointment_date, "", "100109", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100109", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Marriage Application Approval or Rejected
     */
    public function approvalRejection(Request $req)
    {
        $req->validate([
            "applicationId" => "required",
            "status" => "required"
        ]);
        try {

            // Check if the Current User is Finisher or Not
            $mWfRoleUsermap = new WfRoleusermap();
            $mMarriageActiveRegistration = new MarriageActiveRegistration();
            $track = new WorkflowTrack();
            $todayDate = Carbon::now()->format('Y-m-d');
            $userId = authUser($req)->id;

            $details = MarriageActiveRegistration::find($req->applicationId);
            if (!$details)
                throw new Exception("Application Not Found");
            if (isset($details->appointment_date)) {
                if ($details->appointment_date != $todayDate)
                    throw new Exception("Today is not the appointment date. You can't approve the application today");
            } else
                throw new Exception('Appointment Date is not set');
            $userId = authUser($req)->id;
            $getFinisherQuery = $this->getFinisherId($details->workflow_id);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();

            $workflowId = $details->workflow_id;
            $senderRoleId = $details->current_role;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;

            if ($refGetFinisher->role_id != $roleId) {
                return responseMsgs(false, "Forbidden Access", "");
            }
            DB::beginTransaction();

            // Approval
            if ($req->status == 1) {

                if ($details->doc_verify_status == 0)
                    throw new Exception("Document Not Fully Verified");
                // Marriage Application replication

                $approvedMarriage = $details->replicate();
                $approvedMarriage->setTable('marriage_approved_registrations');
                $approvedMarriage->id = $details->id;
                $approvedMarriage->id = $todayDate;
                $approvedMarriage->save();
                $details->delete();

                $msg =  "Application Successfully Approved !!";
                $metaReqs['verificationStatus'] = 1;
            }
            // Rejection
            if ($req->status == 0) {
                // Marriage Application replication

                $rejectedMarriage = $details->replicate();
                $rejectedMarriage->setTable('marriage_rejected_registrations');
                $rejectedMarriage->id = $details->id;
                $rejectedMarriage->save();
                $details->delete();
                $msg =  "Application Rejected !!";
                $metaReqs['verificationStatus'] = 0;
            }

            $metaReqs['moduleId'] = $this->_marriageModuleId;
            $metaReqs['workflowId'] = $details->workflow_id;
            $metaReqs['refTableDotId'] = 'marriage_active_registrations.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['senderRoleId'] = $senderRoleId;
            $metaReqs['user_id'] = $userId;
            $metaReqs['trackDate'] = Carbon::now()->format('Y-m-d H:i:s');
            $req->request->add($metaReqs);
            $track->saveTrack($req);

            // Updation of Received Date
            $preWorkflowReq = [
                'workflowId' => $details->workflow_id,
                'refTableDotId' => 'marriage_active_registrations.id',
                'refTableIdValue' => $req->applicationId,
                'receiverRoleId' => $senderRoleId
            ];
            // $previousWorkflowTrack = $track->getWfTrackByRefId($preWorkflowReq);
            // $previousWorkflowTrack->update([
            //     'forward_date' => Carbon::now()->format('Y-m-d'),
            //     'forward_time' => Carbon::now()->format('H:i:s')
            // ]);
            DB::commit();
            return responseMsgs(true, $msg, "", '100110', '01', responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", '100110', '01', responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Application Edit
     */
    public function editApplication(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'id' => 'required|digits_between:1,9223372036854775807',
            ]);
            if ($validator->fails())
                return validationError($validator);

            $reqs =  $this->makeRequest($req);
            $mMarriageActiveRegistration = MarriageActiveRegistration::find($req->id);
            $mMarriageActiveRegistration->update($reqs);

            return responseMsgs(true, "Data Edited", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    /**
     * | Document Verify Reject (04)
     */
    public function docVerifyReject(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|digits_between:1,9223372036854775807',
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'docRemarks' =>  $req->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
            'docStatus' => 'required|in:Verified,Rejected'
        ]);

        if ($validator->fails())
            return validationError($validator);

        try {
            // Variable Assignments
            $mWfDocument = new WfActiveDocument();
            $mMarriageActiveRegistration = new MarriageActiveRegistration();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $userId = authUser($req)->id;
            $applicationId = $req->applicationId;
            // Derivative Assigments
            $details = MarriageActiveRegistration::find($req->applicationId);
            $safReq = new Request([
                'userId' => $userId,
                'workflowId' => $details->workflow_id
            ]);
            $senderRoleDtls = $mWfRoleusermap->getRoleByUserWfId($safReq);
            if (!$senderRoleDtls || collect($senderRoleDtls)->isEmpty())
                throw new Exception("Role Not Available");

            $senderRoleId = $senderRoleDtls->wf_role_id;

            if ($senderRoleId != $this->_registrarRoleId)                                // Authorization for Dealing Assistant Only
                throw new Exception("You are not Authorized");

            if (!$details || collect($details)->isEmpty())
                throw new Exception("Application Details Not Found");

            $ifFullDocVerified = $this->ifFullDocVerified($applicationId);       // (Current Object Derivative Function 4.1)
            if ($ifFullDocVerified == 1)
                throw new Exception("Document Fully Verified");

            DB::beginTransaction();
            if ($req->docStatus == "Verified") {
                $status = 1;
            }
            if ($req->docStatus == "Rejected") {
                $status = 2;
                // For Rejection Doc Upload Status and Verify Status will disabled
                $details->doc_upload_status = 0;
                $details->doc_verify_status = 0;
                $details->save();
            }

            $reqs = [
                'remarks' => $req->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            if ($req->docStatus == 'Verified')
                $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId, $req->docStatus);
            else
                $ifFullDocVerifiedV1 = 0;                                       // In Case of Rejection the Document Verification Status will always remain false

            // dd($ifFullDocVerifiedV1);
            if ($ifFullDocVerifiedV1 == 1) {                                     // If The Document Fully Verified Update Verify Status
                $details->doc_verify_status = 1;
                $details->save();
            }
            DB::commit();
            return responseMsgs(true, $req->docStatus . " Successfully", "", "100111", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "100111", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check if the Document is Fully Verified or Not (4.1)
     */
    public function ifFullDocVerified($applicationId)
    {
        $mMarriageActiveRegistration = new MarriageActiveRegistration();
        $mWfActiveDocument = new WfActiveDocument();
        $details = MarriageActiveRegistration::find($applicationId);
        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $details->workflow_id,
            'moduleId' => $this->_marriageModuleId,
        ];
        $refDocList = $mWfActiveDocument->getVerifiedDocsByActiveId($refReq);
        return $this->isAllDocs($applicationId, $refDocList, $details);
    }

    /**
     * | 
     */
    public function approvedApplication(Request $req)
    {
        try {
            $perPage = $req->perPage ?? 10;
            $ulbId = authUser($req)->ulb_id;
            $list = MarriageApprovedRegistration::select('marriage_approved_registrations.*', 'tran_no')
                ->leftjoin('marriage_transactions', 'marriage_transactions.application_id', 'marriage_approved_registrations.id')
                ->where('marriage_approved_registrations.ulb_id', $ulbId)
                ->orderByDesc('marriage_approved_registrations.id');

            $approvedList = app(Pipeline::class)
                ->send(
                    $list
                )
                ->through([
                    SearchByApplicationNo::class,
                    SearchByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "Approved Application", $approvedList, 100112, 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 100112, 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /**
     * | Initiate Online Payment
         razor pay request store pending
     */
    public function generateOrderId(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|digits_between:1,9223372036854775807',
        ]);

        try {
            $user               = authUser($req);
            $applicationId      = $req->applicationId;
            $paymentUrl         = $this->_paymentUrl;
            $mMarriageRazorpayRequest = new MarriageRazorpayRequest();
            $marriageDetails = MarriageActiveRegistration::find($applicationId);

            $myRequest = [
                'amount'          => $marriageDetails->payment_amount + $marriageDetails->penalty_amount,
                'workflowId'      => $marriageDetails->workflow_id,
                'id'              => $applicationId,
                'departmentId'    => $this->_marriageModuleId
            ];
            $newRequest = $req->merge($myRequest);

            # Api Calling for OrderId
            $refResponse = Http::withHeaders([
                "api-key" => "eff41ef6-d430-4887-aa55-9fcf46c72c99"                             // Static
            ])
                ->withToken($req->bearerToken())
                ->post($paymentUrl . 'api/payment/generate-orderid', $newRequest);               // Static

            $orderData = json_decode($refResponse);
            $jsonIncodedData = $orderData->data;



            return responseMsgs(true, "Order Id generated successfully", $jsonIncodedData);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | End Online Payment
         razor pay response store pending
     */
    public function storeTransactionDtl(Request $req)
    {
        try {
            $mMarriageTransaction = new MarriageTransaction();
            $mMarriageRazorpayResponse = new MarriageRazorpayResponse();
            $marriageDetails = MarriageActiveRegistration::find($req->id);

            // $razorpayReqs = [
            //     "application_id"      => $req->id,
            //     "razorpay_request_id" => $req->tranDate,
            //     "order_id"            => $req->orderId,
            //     "payment_id"          => $req->paymentId,
            //     "amount"              => $req->amount,
            //     "workflow_id"         => $req->workflowId,
            //     "transaction_no"      => $req->transactionNo,
            //     "citizen_id"          => $req->userId,
            //     "ulb_id"              => $req->ulbId,
            //     "tran_date"           => $req->paymentMode,
            //     "gateway_type"        => $req->gatewayType,
            //     "department_id"       => $req->departmentId,
            // ];

            $transanctionReqs = [
                "application_id" => $req->id,
                "tran_date"      => $req->tranDate,
                "tran_no"        => $req->transactionNo,
                "amount_paid"    => $req->amount,
                "payment_mode"   => $req->paymentMode,
                "amount"         => $marriageDetails->payment_amount,
                "penalty_amount" => $marriageDetails->penalty_amount,
                "workflow_id"    => $marriageDetails->workflow_id,
                "ulb_id"         => $marriageDetails->ulb_id,
                "citizen_id"     => $req->userId,
                "status"         => 1,
            ];
            // DB::beginTransaction();
            $tranDtl = $mMarriageTransaction->store($transanctionReqs);
            // $tranDtl = $mMarriageRazorpayResponse->store($razorpayReqs);
            $marriageDetails->update(["payment_status" => 1]);

            // DB::commit();

            return responseMsgs(true, "Data Received", "", 100117, 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 100117, 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Offline Payment
     */
    public function offlinePayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "applicationId" => "required|integer",
            "paymentMode" => "required",
            "chequeDdNo" => "nullable",
            "chequeDate" => "nullable|date"
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $user = authUser($req);
            $userId = $user->id;

            $idGeneration = new IdGeneration;
            $mMarriageTransaction = new MarriageTransaction();
            $marriageDetails = MarriageActiveRegistration::find($req->applicationId);
            $paymentMode = strtoupper($req->paymentMode);

            if (!$marriageDetails)
                throw new Exception("Application not found");

            if ($marriageDetails->payment_status == 1)
                throw new Exception("Payment Already Done");

            if ($paymentMode != "CASH")
                $status = 2;
            else
                $status = 1;

            $tranNo = $idGeneration->generateTransactionNo($marriageDetails->ulb_id);
            $mReqs = [
                "application_id" => $marriageDetails->id,
                "tran_date"      => Carbon::now(),
                "tran_no"        => $tranNo,
                "amount"         => $marriageDetails->payment_amount,
                "penalty_amount" => $marriageDetails->penalty_amount,
                "amount_paid"    => $marriageDetails->payment_amount + $marriageDetails->penalty_amount,
                "payment_mode"   => $paymentMode,
                "cheque_dd_no"   => $req->chequeDdNo,
                "cheque_date"    => $req->chequeDate,
                "workflow_id"    => $marriageDetails->workflow_id,
                "ulb_id"         => $marriageDetails->ulb_id,
                "user_id"        => $userId,
                "status"         => $status,
            ];
            DB::beginTransaction();
            $tranDtl = $mMarriageTransaction->store($mReqs);
            $tranDtl->tranNo = $tranDtl->tran_no;

            if ($tranDtl->payment_mode == "CASH")
                $marriageDetails->payment_status = 1;
            else
                $marriageDetails->payment_status = 2;
            $marriageDetails->save();

            DB::commit();
            return responseMsgs(true, "Payment Done", $tranDtl, 100115, 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 100115, 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }



    /**
     * | Payment Receipt
     */
    public function paymentReceipt(Request $req)
    {
        try {
            $validator =  Validator::make($req->all(), [
                "transactionNo" => "required"
            ]);

            if ($validator->fails())
                return validationError($validator);

            $mMarriageTransaction = new MarriageTransaction();
            $mUlbMaster = new UlbMaster();
            $tranDtls = $mMarriageTransaction->where('tran_no', $req->transactionNo)->first();

            if (!$tranDtls)
                throw new Exception("Transaction Not Found");
            $marriageDetails = MarriageActiveRegistration::find($tranDtls->application_id);
            $ulbDtl = $mUlbMaster->getUlbDetails($marriageDetails->ulb_id);

            $receiptDtls = [
                "tran_date"           => $tranDtls->tran_date,
                "tran_no"             => $tranDtls->tran_no,
                "payment_mode"        => $tranDtls->payment_mode,
                "total_paid_amount"   => $tranDtls->amount_paid,
                "bride_name"          => $marriageDetails->bride_name,
                "groom_name"          => $marriageDetails->groom_name,
                "marriage_place"      => $marriageDetails->marriage_place,
                "marriage_date"       => $marriageDetails->marriage_date,
                "application_no"      => $marriageDetails->application_no,
                "registration_amount" => $marriageDetails->payment_amount,
                "penalty_amount"      => $marriageDetails->penalty_amount,
                "ulbDetails"          => $ulbDtl
            ];

            return responseMsgs(true, "Payment Receipt", $receiptDtls, 100116, 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 100116, 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Search Application
     */
    public function searchApplication(Request $req)
    {
        try {
            // $validator =  Validator::make($req->all(), [
            //     "applicationNo" => "nullable",
            //     "name" => "nullable",
            // ]);

            // if ($validator->fails())
            //     return validationError($validator);
            $perPage = $req->perPage ?? 10;
            $ulbId = $req->ulbId ?? authUser($req)->ulb_id;
            if (!$ulbId)
                throw new Exception("Ulb id is required");
            $mMarriageTransaction = new MarriageTransaction();
            $mUlbMaster = new UlbMaster();
            $tranDtls = $mMarriageTransaction->where('tran_no', $req->transactionNo)->first();

            $list = MarriageActiveRegistration::select('marriage_active_registrations.*', 'tran_no')
                ->where('marriage_active_registrations.ulb_id', $ulbId)
                ->leftjoin('marriage_transactions', 'marriage_transactions.application_id', 'marriage_active_registrations.id')
                ->orderByDesc('marriage_active_registrations.id');

            $inbox = app(Pipeline::class)
                ->send(
                    $list
                )
                ->through([
                    SearchByApplicationNo::class,
                    SearchByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "Payment Receipt", $inbox, 100118, 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 100118, 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Forward / Backward Application
     */
    public function postNextLevel(Request $req)
    {
        $wfLevels = Config::get('PropertyConstaint.HARVESTING-LABEL');
        try {
            $req->validate([
                'applicationId' => 'required|integer',
                'receiverRoleId' => 'nullable|integer',
                'action' => 'required|In:forward,backward',
            ]);

            $userId = authUser($req)->id;
            $track = new WorkflowTrack();
            $marriageDetails = MarriageActiveRegistration::find($req->applicationId);
            if (!$marriageDetails)
                throw new Exception("Application not found");
            $mWfWorkflows = new WfWorkflow();
            $mWfRoleMaps = new WfWorkflowrolemap();
            $senderRoleId = $marriageDetails->current_role;
            $ulbWorkflowId = $marriageDetails->workflow_id;
            // $req->validate([
            //     'comment' => $senderRoleId == $wfLevels['BO'] ? 'nullable' : 'required',
            // ]);

            $ulbWorkflowMaps = $mWfWorkflows->getWfDetails($ulbWorkflowId);
            $roleMapsReqs = new Request([
                'workflowId' => $ulbWorkflowMaps->id,
                'roleId' => $senderRoleId
            ]);
            $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);

            DB::beginTransaction();
            if ($req->action == 'forward') {
                $wfMstrId = $mWfWorkflows->getWfMstrByWorkflowId($marriageDetails->workflow_id);
                // $this->checkPostCondition($senderRoleId, $wfLevels, $marriageDetails);          // Check Post Next level condition
                $marriageDetails->current_role = $forwardBackwardIds->forward_role_id;
                // $marriageDetails->last_role_id =  $forwardBackwardIds->forward_role_id;         // Update Last Role Id
                $metaReqs['verificationStatus'] = 1;
                $metaReqs['receiverRoleId'] = $forwardBackwardIds->forward_role_id;
            }
            if ($req->action == 'backward') {
                $marriageDetails->current_role = $forwardBackwardIds->backward_role_id;
                $metaReqs['verificationStatus'] = 0;
                $metaReqs['receiverRoleId'] = $forwardBackwardIds->backward_role_id;
            }

            $marriageDetails->save();
            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $marriageDetails->workflow_id;
            $metaReqs['refTableDotId'] = 'marriage_active_registrations.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['senderRoleId'] = $senderRoleId;
            $metaReqs['user_id'] = $userId;

            $req->request->add($metaReqs);
            $track->saveTrack($req);
            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", '011110', 01, '446ms', 'Post', $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}

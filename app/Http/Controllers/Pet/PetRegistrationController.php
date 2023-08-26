<?php

namespace App\Http\Controllers\Pet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pet\PetEditReq;
use App\Http\Requests\Pet\PetEditRequests;
use App\Http\Requests\Pet\PetRegistrationReq;
use App\MicroServices\DocumentUpload;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\ActiveCitizenUndercare;
use App\Models\Advertisements\RefRequiredDocument;
use App\Models\Advertisements\WfActiveDocument;
use App\Models\ApiMaster;
use App\Models\Pet\MPetFee;
use App\Models\Pet\MPetOccurrenceType;
use App\Models\Pet\PetActiveApplicant;
use App\Models\Pet\PetActiveDetail;
use App\Models\Pet\PetActiveRegistration;
use App\Models\Pet\PetApprovedRegistration;
use App\Models\Pet\PetAudit;
use App\Models\Pet\PetRegistrationCharge;
use App\Models\Pet\PetRenewalApplicant;
use App\Models\Pet\PetRenewalDetail;
use App\Models\Pet\PetRenewalRegistration;
use App\Models\Pet\PetTran;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropFloor;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSaf;
use App\Models\Workflows\CustomDetail;
use App\Models\Workflows\UlbWardMaster;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WorkflowMap;
use App\Models\Workflows\WorkflowTrack;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use PhpParser\Node\Expr\Empty_;

/**
 * | Created On-02-01-20222 
 * | Created By- Sam Kerketta
 * | Pet Registration Operations
 */

class PetRegistrationController extends Controller
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
    private $_applyMode;
    private $_tranType;
    private $_tableName;
    # Class constructer 
    public function __construct()
    {
        $this->_masterDetails       = Config::get("pet.MASTER_DATA");
        $this->_propertyType        = Config::get("pet.PROP_TYPE");
        $this->_occupancyType       = Config::get("pet.PROP_OCCUPANCY_TYPE");
        $this->_workflowMasterId    = Config::get("pet.WORKFLOW_MASTER_ID");
        $this->_petParamId          = Config::get("pet.PARAM_ID");
        $this->_petModuleId         = Config::get('pet.PET_MODULE_ID');
        $this->_userType            = Config::get("pet.REF_USER_TYPE");
        $this->_petWfRoles          = Config::get("pet.ROLE_LABEL");
        $this->_docReqCatagory      = Config::get("pet.DOC_REQ_CATAGORY");
        $this->_dbKey               = Config::get("pet.DB_KEYS");
        $this->_fee                 = Config::get("pet.FEE_CHARGES");
        $this->_applicationType     = Config::get("pet.APPLICATION_TYPE");
        $this->_applyMode           = Config::get("pet.APPLY_MODE");
        $this->_tranType            = Config::get("pet.TRANSACTION_TYPE");
        $this->_tableName           = Config::get("pet.TABLE_NAME");
    }

    /**
     * | Get all master data
     * | Collect the master data related pet module
        | Serial No : 0
        | Working
     */
    public function getAllMasters(Request $req)
    {
        try {
            $mMPetOccurrenceType = new MPetOccurrenceType();
            $occurenceType = $mMPetOccurrenceType->listOccurenceType()
                ->select('id', 'occurrence_types')
                ->get();
            $is_occurence_exist = collect($occurenceType)->first();
            if (is_null($is_occurence_exist)) {
                throw new Exception("master Data not Found!");
            }
            $refMasterDetails       = $this->_masterDetails;
            $registrationThrough    = $this->formatTheArray($refMasterDetails['REGISTRATION_THROUGH'], "registration_through");
            $ownertype              = $this->formatTheArray($refMasterDetails['OWNER_TYPE_MST'], "owner_type");
            $petGender              = $this->formatTheArray($refMasterDetails['PET_GENDER'], "pet_gender");

            $returnData = [
                "occurenceType"         => $occurenceType,
                "registrationThrough"   => $registrationThrough,
                "ownertype"             => $ownertype,
                "petGender"             => $petGender
            ];
            $message = "list for Pet Module's master data!";
            return responseMsgs(true, $message, $returnData, "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }


    /**
     * | Use for data structure for master data from config 
        | Serial No : 0
        | Working 
     */
    public function formatTheArray($array, $refname)
    {
        $returnData = array();
        foreach ($array as $key => $value) {
            $array = [
                "id" => $value,
                "$refname" => $key
            ];
            array_push($returnData, $array);
        }
        return $returnData;
    }


    /**
     * | Apply for the pet Registration
     * | Save form data 
     * | @param req
        | Serial No : 0
        | Need Modifications in saving charges
        | Working 
     */
    public function applyPetRegistration(PetRegistrationReq $req)
    {
        try {
            $mPetActiveDetail           = new PetActiveDetail();
            $mPetActiveRegistration     = new PetActiveRegistration();
            $mPetActiveApplicant        = new PetActiveApplicant();
            $mPetApprovedRegistration   = new PetApprovedRegistration();
            $mWfWorkflow                = new WfWorkflow();
            $mMPetFee                   = new MPetFee();
            $mPetRegistrationCharge     = new PetRegistrationCharge();
            $user                       = authUser($req);
            $ulbId                      = $req->ulbId ?? 2;
            $workflowMasterId           = $this->_workflowMasterId;
            $petParamId                 = $this->_petParamId;
            $feeId                      = $this->_fee;
            $confApplicationType        = $this->_applicationType;
            $confApplyThrough           = $this->_masterDetails['REGISTRATION_THROUGH'];

            # Get iniciater and finisher for the workflow 
            $ulbWorkflowId = $mWfWorkflow->getulbWorkflowId($workflowMasterId, $ulbId);
            if (!$ulbWorkflowId) {
                throw new Exception("Respective Ulb is not maped to 'Pet Registration' Workflow!");
            }
            $registrationCharges = $mMPetFee->getFeeById($feeId['REGISTRATION_RENEWAL']);
            if (!$registrationCharges) {
                throw new Exception("Currently charges are not available!");
            }
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);
            $refFinisherRoleId  = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId     = collect(DB::select($refFinisherRoleId))->first()->role_id;
            $initiatorRoleId    = collect(DB::select($refInitiatorRoleId))->first()->role_id;

            $refValidatedDetails = $this->checkParamForRegister($req);

            # Data Base interaction 
            DB::beginTransaction();
            $idGeneration = new PrefixIdGenerator($petParamId['REGISTRATION'], $ulbId);
            $petApplicationNo = $idGeneration->generate();
            $refData = [
                "finisherRoleId"    => $finisherRoleId,
                "initiatorRoleId"   => $initiatorRoleId,
                "workflowId"        => $ulbWorkflowId->id,
                "applicationNo"     => $petApplicationNo,
            ];
            if ($req->applyThrough == $confApplyThrough['Holding']) {
                $refData["holdingNo"] = collect($refValidatedDetails['propDetails'])['holding_no'] ?? null;
            }
            if ($req->applyThrough == $confApplyThrough['Saf']) {
                $refData["safNo"] = collect($refValidatedDetails['propDetails'])['saf_no'] ?? null;
            }
            $req->merge($refData);

            # Renewal and the New Registration
            if ($req->isRenewal == 0 || !isset($req->isRenewal)) {
                if (isset($req->registrationId)) {
                    throw new Exception("Registration No is Not Req for new Pet Registraton!");
                }
                $refData = [
                    "applicationType"   => "New_Apply",
                    "applicationTypeId" => $confApplicationType['NEW_APPLY']
                ];
                $req->merge($refData);
            }
            if ($req->isRenewal == 1) {
                $refData = [
                    "applicationType"   => "Renewal",
                    "registrationId"    => $req->registrationId,
                    "applicationTypeId" => $confApplicationType['RENEWAL']
                ];
                $req->merge($refData);
                $mPetApprovedRegistration->deactivateOldRegistration($req->registrationId);
            }
            # Save active details 
            $applicationDetails = $mPetActiveRegistration->saveRegistration($req, $user);
            $mPetActiveApplicant->saveApplicants($req, $applicationDetails['id']);
            $mPetActiveDetail->savePetDetails($req, $applicationDetails['id']);

            # Save registration charges
            $metaRequest = new Request([
                "applicationId"     => $applicationDetails['id'],
                "applicationType"   => $req->applicationType,
                "amount"            => $registrationCharges->amount,
                "registrationFee"   => $registrationCharges->amount,
                "applicationTypeId" => $req->applicationTypeId
            ]);
            $mPetRegistrationCharge->saveRegisterCharges($metaRequest);
            DB::commit();

            $returnData = [
                "id" => $applicationDetails['id'],
                "applicationNo" => $applicationDetails['applicationNo'],
            ];
            return responseMsgs(true, "Pet Registration application submitted!", $returnData, "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Check params before pet registration 
     * | Check property and saf details 
     * | @param req
        | for Http Request
        | $transfer = ["holdingNo" => $req->propertyNo];
                $refApi = $mApiMaster->getApiEndPoint($confApiId['get_prop_detils'])->first();
                Http::withHeaders([
                    "Content-Type" => "$confHttpHeaders"
                ])
                    ->withToken('19013|TpteWXV08A7wYKVNwLrVpdaOO1oC1tyJDxE7LVwN')                       // Static
                    ->post("$refApi->end_point", $transfer);
        | Serial No : 0
        | Working
     */
    public function checkParamForRegister($req)
    {
        $mPropProperty          = new PropProperty();
        $mPropFloor             = new PropFloor();
        $mPropActiveSaf         = new PropActiveSaf();
        $mPropActiveSafsFloor   = new PropActiveSafsFloor();

        $confApplyThrough   = $this->_masterDetails['REGISTRATION_THROUGH'];
        $confPropertyType   = $this->_propertyType;
        $ownertype          = $this->_masterDetails['OWNER_TYPE_MST'];

        switch ($req->applyThrough) {
            case ($req->applyThrough == $confApplyThrough['Holding']):
                $refPropDetails = $mPropProperty->getPropDtls()->where('prop_properties.holding_no', $req->propertyNo)
                    ->first();
                if (is_null($refPropDetails)) {
                    throw new Exception("property according to $req->propertyNo not found!");
                }
                if ($refPropDetails->prop_type_mstr_id != $confPropertyType['VACANT_LAND']) {
                    $floorsDetails = $mPropFloor->getPropFloors($refPropDetails->id)->get();
                    $isTenant = $this->getPropOccupancyType($floorsDetails);
                    if ($req->ownerCategory == $ownertype['Tenant'] && $isTenant == false) {
                        throw new Exception("Respective property dont have tenant!");
                    }
                }
                if ($refPropDetails->prop_type_mstr_id == $confPropertyType['VACANT_LAND']) {
                    throw new Exception("Pet cannot be applied in VACANT LAND!");
                }
                $returnDetails = [
                    "tenant"        => $isTenant,
                    "propDetails"   => $refPropDetails,
                ];
                break;

            case ($req->applyThrough == $confApplyThrough['Saf']):
                $refSafDetails = $mPropActiveSaf->getSafDtlBySaf()->where('s.saf_no', $req->propertyNo)
                    ->first();
                if (is_null($refSafDetails)) {
                    throw new Exception("property according to $req->propertyNo not found!");
                }
                if ($refSafDetails->prop_type_mstr_id != $confPropertyType['VACANT_LAND']) {
                    $floorsDetails = $mPropActiveSafsFloor->getSafFloors($refSafDetails->id)->get();
                    $isTenant = $this->getPropOccupancyType($floorsDetails);
                    if ($req->ownerCategory == $ownertype['Tenant'] && $isTenant == false) {
                        throw new Exception("Respective property dont have tenant!");
                    }
                }
                if ($refSafDetails->prop_type_mstr_id == $confPropertyType['VACANT_LAND']) {
                    throw new Exception("Pet cannot be applied in VACANT LAND!");
                }
                $returnDetails = [
                    "tenant"        => $isTenant,
                    "propDetails"   => $refSafDetails,
                ];
                break;
        }
        return $returnDetails;
    }

    /**
     * | Get occupancy type accordingly for saf and holding
        | Serial No : 0
        | Working
     */
    public function getPropOccupancyType($floorDetails)
    {
        $confOccupancyType  = $this->_occupancyType;
        $refOccupancyType   = collect($confOccupancyType)->flip();
        $isTenanted = collect($floorDetails)
            ->where('occupancy_type_mstr_id', $refOccupancyType['TENANTED'])
            ->first();

        if ($isTenanted) {
            return true;                               // Static
        }
        return false;                                   // Static
    }


    /**
     * |---------------------------- Get Document Lists To Upload ----------------------------|
     * | Doc Upload for the Workflow
        | Serial No : 0
        | Working
     */
    public function getDocToUpload(Request $req)
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
            $mPetActiveRegistration     = new PetActiveRegistration();
            $petApplicationId           = $req->applicationId;

            $refPetApplication = $mPetActiveRegistration->getPetApplicationById($petApplicationId)->first();                      // Get Pet Details
            if (is_null($refPetApplication)) {
                throw new Exception("Application Not Found for respective ($petApplicationId) id!");
            }
            // check if the respective is working on the front end
            // $this->checkAutheriseUser($req);
            $documentList = $this->getPetDocLists($refPetApplication);
            $petTypeDocs['listDocs'] = collect($documentList)->map(function ($value) use ($refPetApplication) {
                return $this->filterDocument($value, $refPetApplication)->first();
            });
            $totalDocLists = collect($petTypeDocs);
            $totalDocLists['docUploadStatus']   = $refPetApplication->doc_upload_status;
            $totalDocLists['docVerifyStatus']   = $refPetApplication->doc_verify_status;
            $totalDocLists['ApplicationNo']     = $refPetApplication->application_no;
            $totalDocLists['paymentStatus']     = $refPetApplication->payment_status;
            return responseMsgs(true, "", remove_null($totalDocLists), "010203", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", "", 'POST', "");
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
     * | Calling Function 
        | Serial No : 
        | Working
     */
    public function filterDocument($documentList, $refPetApplication, $ownerId = null)
    {
        $mWfActiveDocument  = new WfActiveDocument();
        $applicationId      = $refPetApplication->ref_application_id;
        $workflowId         = $refPetApplication->workflow_id;
        $moduleId           = $this->_petModuleId;
        $confDocReqCatagory = $this->_docReqCatagory;
        $uploadedDocs       = $mWfActiveDocument->getDocByRefIds($applicationId, $workflowId, $moduleId);

        $explodeDocs = collect(explode('#', $documentList->requirements));
        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs, $ownerId, $confDocReqCatagory) {

            # var defining
            $document   = explode(',', $explodeDoc);
            $key        = array_shift($document);
            $label      = array_shift($document);
            $documents  = collect();

            collect($document)->map(function ($item) use ($uploadedDocs, $documents, $ownerId) {
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
            switch ($key) {
                case ($confDocReqCatagory['1']):
                    $reqDoc['isMandatory'] = 1;                                                 // Static
                    break;
                case ($confDocReqCatagory['2']):
                    $reqDoc['isMandatory'] = 1;                                                 // Static
                    break;
                case ($confDocReqCatagory['3']):
                    $reqDoc['isMandatory'] = 0;                                                 // Static
                    break;
            }

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
     * | List of the doc to upload 
     * | Calling function
        | Serial No :  
        | Working
     */
    public function getPetDocLists($application)
    {
        $mRefRequiredDocument   = new RefRequiredDocument();
        $confPetModuleId        = $this->_petModuleId;
        $confOwnerType          = $this->_masterDetails['OWNER_TYPE_MST'];

        $type = ["PET_VACCINATION", "ADDRESS PROOF", "LEPTOSPIROSIS_VACCINATION"];
        if ($application->owner_type == $confOwnerType['Tenant'])         // Holding No, SAF No // Static
        {
            $type = ["TENANTED", "NOC"];
        }
        return $mRefRequiredDocument->getCollectiveDocByCode($confPetModuleId, $type);
    }

    /**
     * | Read the server url 
        | Common function
        | Serial No : 
        | Working
     */
    public function readDocumentPath($path)
    {
        $path = (config('app.url') . ":8001" . "/" . $path);
        return $path;
    }


    /**
     * | Upload Application Documents 
     * | @param req
        | Serial No :
        | Working 
        | Look on the concept of deactivation of the rejected documents 
        | Put the static "verify status" 2 in config  
     */
    public function uploadPetDoc(Request $req)
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
            $mPetActiveRegistration     = new PetActiveRegistration();
            $relativePath               = Config::get('pet.PET_RELATIVE_PATH');
            $refmoduleId                = $this->_petModuleId;
            $confUserType               = $this->_userType;

            $getPetDetails  = $mPetActiveRegistration->getPetApplicationById($applicationId)->firstOrFail();
            $refImageName   = $req->docCode;
            $refImageName   = $getPetDetails->ref_application_id . '-' . str_replace(' ', '_', $refImageName);
            $imageName      = $refDocUpload->upload($refImageName, $document, $relativePath['REGISTRATION']);

            $metaReqs = [
                'moduleId'      => $refmoduleId,
                'activeId'      => $getPetDetails->ref_application_id,
                'workflowId'    => $getPetDetails->workflow_id,
                'ulbId'         => $getPetDetails->ulb_id,
                'relativePath'  => $relativePath['REGISTRATION'],
                'document'      => $imageName,
                'docCode'       => $req->docCode,
                'ownerDtlId'    => $req->ownerId ?? null,
                'docCategory'   => $req->docCategory
            ];

            if ($user->user_type == $confUserType['1']) {
                $isCitizen = true;
                $this->checkParamForDocUpload($isCitizen, $getPetDetails, $user);
            } else {
                $isCitizen = false;
                $this->checkParamForDocUpload($isCitizen, $getPetDetails, $user);
            }

            DB::beginTransaction();
            $ifDocExist = $mWfActiveDocument->isDocCategoryExists($getPetDetails->ref_application_id, $getPetDetails->workflow_id, $refmoduleId, $req->docCategory, $req->ownerId);   // Checking if the document is already existing or not
            $metaReqs = new Request($metaReqs);
            if (collect($ifDocExist)->isEmpty()) {
                $mWfActiveDocument->postPetDocuments($metaReqs);
            }
            if ($ifDocExist) {
                $mWfActiveDocument->editDocuments($ifDocExist, $metaReqs);
            }

            #check full doc upload
            $refCheckDocument = $this->checkFullDocUpload($req);
            # Update the Doc Upload Satus in Application Table
            if ($refCheckDocument->contains(false) && $getPetDetails->doc_upload_status == true) {
                $mPetActiveRegistration->updateUploadStatus($applicationId, false);
            }
            if ($refCheckDocument->unique()->count() === 1 && $refCheckDocument->unique()->first() === true) {
                $mPetActiveRegistration->updateUploadStatus($req->applicationId, true);
            }
            # if the application is parked and btc 
            // if ($getPetDetails->parked == true) {
            //     $mWfActiveDocument->deactivateRejectedDoc($metaReqs);
            //     $refReq = new Request([
            //         'applicationId' => $applicationId
            //     ]);
            //     $documentList = $this->getUploadDocuments($refReq);
            //     $DocList = collect($documentList)['original']['data'];
            //     $refVerifyStatus = $DocList->where('doc_category', '!=', $req->docCategory)->pluck('verify_status');
            //     if (!in_array(2, $refVerifyStatus->toArray())) {                                                        // Static "2" for rejected doc
            //         $status = false;
            //         $getPetDetails->updateParkedstatus($status, $applicationId);
            //     }
            // }
            DB::commit();
            return responseMsgs(true, "Document Uploadation Successful", "", "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Check if the params for document upload
     * | @param isCitizen
     * | @param applicantDetals
     * | @param user
        | Serial No :
        | Working 
     */
    public function checkParamForDocUpload($isCitizen, $applicantDetals, $user)
    {
        $refWorkFlowMaster = Config::get('workflow-constants.WATER_MASTER_ID');
        switch ($isCitizen) {
            case (true): # For citizen 
                if (!is_null($applicantDetals->current_role) && $applicantDetals->parked == true) {
                    return true;
                }
                if (!is_null($applicantDetals->current_role)) {
                    throw new Exception("You aren't allowed to upload document!");
                }
                break;
            case (false): # For user
                $userId = $user->id;
                $ulbId = $applicantDetals->ulb_id;
                $role = $this->getUserRoll($userId, $ulbId, $refWorkFlowMaster);
                if (is_null($role)) {
                    throw new Exception("You dont have any role!");
                }
                if ($role->can_upload_document != true) {
                    throw new Exception("You dont have permission to upload Document!");
                }
                break;
        }
    }



    /**
     * | Caheck the Document if Fully Upload or not
     * | @param req
        | Working
        | Serial No :
     */
    public function checkFullDocUpload($req)
    {
        # Check the Document upload Status
        $confDocReqCatagory = $this->_docReqCatagory;
        $documentList = $this->getDocToUpload($req);
        $refDoc = collect($documentList)['original']['data']['listDocs'];
        $checkDocument = collect($refDoc)->map(function ($value)
        use ($confDocReqCatagory) {
            if ($value['docType'] == $confDocReqCatagory['1'] || $value['docType'] == $confDocReqCatagory['2']) {
                $doc = collect($value['uploadedDoc'])->first();
                if (is_null($doc)) {
                    return false;
                }
                return true;
            }
            return true;
        });
        return $checkDocument;
    }


    /**
     * | Get the upoaded docunment
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
            $mWfActiveDocument      = new WfActiveDocument();
            $mPetActiveRegistration = new PetActiveRegistration();
            $moduleId               = $this->_petModuleId;

            $petDetails = $mPetActiveRegistration->getPetApplicationById($req->applicationId)->first();
            if (is_null($petDetails))
                throw new Exception("Application Not Found for this ($req->applicationId) application Id!");

            $workflowId = $petDetails->workflow_id;
            $documents = $mWfActiveDocument->getWaterDocsByAppNo($req->applicationId, $workflowId, $moduleId)
                ->where('d.status', '!=', 0)
                ->get();
            $returnData = collect($documents)->map(function ($value) {
                $path =  $this->readDocumentPath($value->ref_doc_path);
                $value->doc_path = !empty(trim($value->ref_doc_path)) ? $path : null;
                return $value;
            });
            return responseMsgs(true, "Uploaded Documents", remove_null($returnData), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Application list for the respective user 
     * | List the application filled by the user 
        | Serial No :
        | Working
     */
    public function getApplicationList(Request $req)
    {
        try {
            $user                   = authUser($req);
            $confUserType           = $this->_userType;
            $confDbKey              = $this->_dbKey;
            $mPetActiveRegistration = new PetActiveRegistration();

            if ($user->user_type != $confUserType['1']) {                                       // If not a citizen
                throw new Exception("You are not an autherised Citizen!");
            }
            # Collect querry Exceptions 
            try {
                $refAppDetails = $mPetActiveRegistration->getAllApplicationDetails($user->id, $confDbKey['1'])
                    ->select(
                        DB::raw("REPLACE(pet_active_registrations.application_type, '_', ' ') AS ref_application_type"),
                        DB::raw("TO_CHAR(pet_active_registrations.application_apply_date, 'DD-MM-YYYY') as ref_application_apply_date"),
                        "pet_active_registrations.*",
                        "pet_active_applicants.applicant_name",
                    )
                    ->orderByDesc('pet_active_registrations.id')
                    ->get();
            } catch (QueryException $q) {
                return responseMsgs(false, "An error occurred during the query!", $q->getMessage(), "", "01", ".ms", "POST", $req->deviceId);
            }
            return responseMsgs(true, "list of active registration!", remove_null($refAppDetails), "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    //BEGIN///////////////////////////////////////////////////////////////////////////////
    /**
     * | Get Application details for workflow view 
     * | @param request
     * | @var ownerDetails
     * | @var applicantDetails
     * | @var applicationDetails
     * | @var returnDetails
     * | @return returnDetails : list of individual applications
        | Serial No : 08
        | Workinig 
     */
    public function getApplicationsDetails(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'applicationId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            # object assigning              
            $mPetActiveRegistration = new PetActiveRegistration();
            $mPetActiveApplicant    = new PetActiveApplicant();
            $mWorkflowMap           = new WorkflowMap();
            $mWorkflowTracks        = new WorkflowTrack();
            $mCustomDetails         = new CustomDetail();
            $applicationId          = $request->applicationId;
            $aplictionList          = array();

            # application details
            $applicationDetails = $mPetActiveRegistration->getPetApplicationById($applicationId)->first();
            if (!$applicationDetails) {
                throw new Exception("Application data according to $request->applicationId not found");
            }
            # owner Details
            $applyDate = Carbon::createFromFormat('Y-m-d', $applicationDetails->application_apply_date)->format('d-m-Y');
            $aplictionList['application_no'] = $applicationDetails->application_no;
            $aplictionList['apply_date']     = $applyDate;

            # DataArray
            $basicDetails       = $this->getBasicDetails($applicationDetails);
            $propertyDetails    = $this->getpropertyDetails($applicationDetails);
            $petDetails         = $this->getrefPetDetails($applicationDetails);

            $firstView = [
                'headerTitle'   => 'Basic Details',
                'data'          => $basicDetails
            ];
            $secondView = [
                'headerTitle'   => 'Applicant Property Details',
                'data'          => $propertyDetails
            ];
            $thirdView = [
                'headerTitle'   => 'Pet Details',
                'data'          => $petDetails
            ];
            $fullDetailsData['fullDetailsData']['dataArray'] = new collection([$firstView, $secondView, $thirdView]);

            # CardArray
            $cardDetails = $this->getCardDetails($applicationDetails);
            $cardData = [
                'headerTitle' => 'Pet Registration',
                'data' => $cardDetails
            ];
            $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardData);

            # TableArray
            $ownerDetail = $mPetActiveApplicant->getApplicationDetails($applicationId)->get();
            $ownerList = $this->getOwnerDetails($ownerDetail);
            $ownerView = [
                'headerTitle' => 'Owner Details',
                'tableHead' => ["#", "Owner Name", "Mobile No", "Email", "Pan"],
                'tableData' => $ownerList
            ];
            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerView]);

            # Level comment
            $mtableId = $applicationDetails->ref_application_id;
            $mRefTable = "pet_active_registrations.id";                         // Static
            $levelComment['levelComment'] = $mWorkflowTracks->getTracksByRefId($mRefTable, $mtableId);

            #citizen comment
            $refCitizenId = $applicationDetails->citizen_id;
            $citizenComment['citizenComment'] = $mWorkflowTracks->getCitizenTracks($mRefTable, $mtableId, $refCitizenId);

            # Role Details
            $metaReqs = [
                'customFor'     => 'Pet',
                'wfRoleId'      => $applicationDetails->current_role_id,
                'workflowId'    => $applicationDetails->workflow_id,
                'lastRoleId'    => $applicationDetails->last_role_id
            ];
            $request->request->add($metaReqs);
            $roleDetails['roleDetails'] = $mWorkflowMap->getRoleDetails($request);

            # Timeline Data
            $timelineData['timelineData'] = collect($request);

            # Departmental Post
            $custom = $mCustomDetails->getCustomDetails($request);
            $departmentPost['departmentalPost'] = $custom;

            # Payments Details
            // return array_merge($aplictionList, $fullDetailsData,$levelComment,$citizenComment,$roleDetails,$timelineData,$departmentPost);
            $returnValues = array_merge($aplictionList, $fullDetailsData, $levelComment, $citizenComment, $roleDetails, $timelineData, $departmentPost);
            return responseMsgs(true, "listed Data!", $returnValues, "", "02", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "02", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * |------------------ Basic Details ------------------|
     * | @param applicationDetails
     * | @var collectionApplications
        | Serial No : 08.01
        | Workinig 
     */
    public function getBasicDetails($applicationDetails)
    {
        if ($applicationDetails->apply_through == 1) {
            $applyThrough = "Holding";
        } else {
            $applyThrough = "Saf";
        }
        $applyDate = Carbon::createFromFormat('Y-m-d', $applicationDetails->application_apply_date)->format('d-m-Y');
        return new Collection([
            ['displayString' => 'Ward No',              'key' => 'WardNo',                  'value' => $applicationDetails->ward_name],
            ['displayString' => 'Type of Connection',   'key' => 'TypeOfConnection',        'value' => $applicationDetails->application_type],
            ['displayString' => 'Registration Through', 'key' => 'RegistrationThrough',     'value' => $applyThrough],
            ['displayString' => 'Apply From',           'key' => 'ApplyFrom',               'value' => $applicationDetails->apply_mode],
            ['displayString' => 'Apply Date',           'key' => 'ApplyDate',               'value' => $applyDate]
        ]);
    }

    /**
     * |------------------ Property Details ------------------|
     * | @param applicationDetails
     * | @var propertyDetails
     * | @var collectionApplications
        | Serial No : 08.02
        | Workinig 
     */
    public function getpropertyDetails($applicationDetails)
    {
        $propertyDetails = array();
        if (!is_null($applicationDetails->holding_no)) {
            array_push($propertyDetails, ['displayString' => 'Holding No',    'key' => 'AppliedBy',  'value' => $applicationDetails->holding_no]);
        }
        if (!is_null($applicationDetails->saf_no)) {
            array_push($propertyDetails, ['displayString' => 'Saf No',        'key' => 'AppliedBy',   'value' => $applicationDetails->saf_no]);
        }
        if ($applicationDetails->owner_type == 1) {
            $ownerType = "Owner";
        } else {
            $ownerType = "Tenant";
        }
        array_push($propertyDetails, ['displayString' => 'Ward No',       'key' => 'WardNo',      'value' => $applicationDetails->ward_name]);
        array_push($propertyDetails, ['displayString' => 'Address',       'key' => 'Address',     'value' => $applicationDetails->address]);
        array_push($propertyDetails, ['displayString' => 'Owner Type',    'key' => 'OwnerType',   'value' => $ownerType]);

        return $propertyDetails;
    }

    /**
     * |------------------ Owner details ------------------|
     * | @param ownerDetails
        | Serial No : 08.04
        | Workinig 
     */
    public function getrefPetDetails($applicationDetails)
    {
        if ($applicationDetails->sex == 1) {
            $sex = "Male";
        } else {
            $sex = "Female";
        }
        $dob = Carbon::createFromFormat('Y-m-d', $applicationDetails->dob)->format('d-m-Y');
        $rabiesVac = Carbon::createFromFormat('Y-m-d', $applicationDetails->rabies_vac_date)->format('d-m-Y');
        $lepVac = Carbon::createFromFormat('Y-m-d', $applicationDetails->leptospirosis_vac_date)->format('d-m-Y');

        if ($applicationDetails->pet_type == 1) {
            $petType = "Dog";
        } else {
            $petType = "Cat";
        }
        return new Collection([
            ['displayString' => 'Pet Name',                         'key' => 'PetName',                         'value' => $applicationDetails->ward_name],
            ['displayString' => 'Pet Type',                         'key' => 'PetType',                         'value' => $petType],
            ['displayString' => 'Sex',                              'key' => 'Sex',                             'value' => $sex],
            ['displayString' => 'Breed',                            'key' => 'Breed',                           'value' => $applicationDetails->breed],
            ['displayString' => 'Veterinary Doctor Name',           'key' => 'VeterinaryDoctorName',            'value' => $applicationDetails->vet_doctor_name],
            ['displayString' => 'Doctor Registration No',           'key' => 'DoctorRegistrationNo',            'value' => $applicationDetails->doctor_registration_no],
            ['displayString' => 'Pet DOB',                          'key' => 'PetDob',                          'value' => $dob],
            ['displayString' => 'Rabies Vaccination Date',          'key' => 'RabiesVaccinationDate',           'value' => $rabiesVac],
            ['displayString' => 'Leptospirosis Vaccination Date',   'key' => 'LeptospirosisVaccinationDate',    'value' => $lepVac],
        ]);
    }

    /**
     * |------------------ Owner details ------------------|
     * | @param ownerDetails
        | Serial No : 08.04
        | Workinig 
     */
    public function getOwnerDetails($ownerDetails)
    {
        return collect($ownerDetails)->map(function ($value, $key) {
            return [
                $key + 1,
                $value['applicant_name'],
                $value['mobile_no'],
                $value['email'],
                $value['pan_no']
            ];
        });
    }

    /**
     * |------------------ Get Card Details ------------------|
     * | @param applicationDetails
     * | @param ownerDetails
     * | @var ownerDetail
     * | @var collectionApplications
        | Serial No : 08.05
        | Workinig 
     */
    public function getCardDetails($applicationDetails)
    {
        if ($applicationDetails->pet_type == 1) {
            $petType = "Dog";
        } else {
            $petType = "Cat";
        }
        if ($applicationDetails->apply_through == 1) {
            $applyThrough = "Holding";
        } else {
            $applyThrough = "Saf";
        }
        $applyDate = Carbon::createFromFormat('Y-m-d', $applicationDetails->application_apply_date)->format('d-m-Y');
        return new Collection([
            ['displayString' => 'Ward No.',             'key' => 'WardNo.',             'value' => $applicationDetails->ward_name],
            ['displayString' => 'Application No.',      'key' => 'ApplicationNo.',      'value' => $applicationDetails->application_no],
            ['displayString' => 'Owner Name',           'key' => 'OwnerName',           'value' => $applicationDetails->applicant_name],
            ['displayString' => 'Pet Type',             'key' => 'PetType',             'value' => $petType],
            ['displayString' => 'Connection Type',      'key' => 'ConnectionType',      'value' => $applicationDetails->application_type],
            ['displayString' => 'Connection Through',   'key' => 'ConnectionThrough',   'value' => $applyThrough],
            ['displayString' => 'Apply-Date',           'key' => 'ApplyDate',           'value' => $applyDate],
        ]);
    }


    ///////////////////////////////////////////////////////////////////////////////END

    /**
     * | Get application details by application id
     * | collective data with registration charges
        | Serial No :
        | Under construction
     */
    public function getApplicationDetails(Request $req)
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
            $applicationId          = $req->applicationId;
            $mPetActiveRegistration = new PetActiveRegistration();
            $mPetRegistrationCharge = new PetRegistrationCharge();

            $applicationDetails = $mPetActiveRegistration->getPetApplicationById($applicationId)->first();
            if (is_null($applicationDetails)) {
                throw new Exception("application Not found!");
            }
            $chargeDetails = $mPetRegistrationCharge->getChargesbyId($applicationDetails->ref_application_id)
                ->select(
                    'id AS chargeId',
                    'amount',
                    'registration_fee',
                    'paid_status',
                    'charge_category',
                    'charge_category_name'
                )
                ->first();
            if (is_null($chargeDetails)) {
                throw new Exception("Charges for respective application not found!");
            }
            $chargeDetails['roundAmount'] = round($chargeDetails['amount']);
            $applicationDetails['charges'] = $chargeDetails;
            return responseMsgs(true, "Listed application details!", remove_null($applicationDetails), "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Delete the Application before payment 
        | Serial No : 
        | Caution 
        | Working
        | Cross Check
     */
    public function deletePetApplication(Request $req)
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
            $user                       = authUser($req);
            $applicationId              = $req->applicationId;
            $confPetModuleId            = $this->_petModuleId;
            $mPetActiveRegistration     = new PetActiveRegistration();
            $mWfActiveDocument          = new WfActiveDocument();
            $mPetRegistrationCharge     = new PetRegistrationCharge();

            $applicantDetals = $mPetActiveRegistration->getPetApplicationById($applicationId)->firstOrFail();
            $this->checkParamsForDelete($applicantDetals, $user);

            DB::beginTransaction();
            $mPetActiveRegistration->deleteApplication($applicationId);
            $mWfActiveDocument->deleteDocuments($applicationId, $applicantDetals->workflow_id, $confPetModuleId);
            $mPetRegistrationCharge->deleteCharges($applicationId);
            DB::commit();
            return responseMsgs(true, "Application Successfully Deleted", "", "", "1.0", "", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Check the parameter for deleting Application 
     * | @param applicationDetails
     * | @param user
        | Serial No :
        | Working
     */
    public function checkParamsForDelete($applicationDetails, $user)
    {
        $applyMode              = $this->_applyMode;
        $trantype               = $this->_tranType;
        $applyMode              = collect($applyMode)->flip();
        $mPetTran               = new PetTran();
        $mPetRegistrationCharge = new PetRegistrationCharge();
        $applicationId          = $applicationDetails->ref_application_id;

        if (is_null($applicationDetails)) {
            throw new Exception("Relted Data or Owner not found!");
        }
        if ($applicationDetails->payment_status == 1) {
            throw new Exception("Your paymnet is done application Cannot be Deleted!");
        }
        if (!is_null($applicationDetails->current_role)) {
            throw new Exception("application is under process can't be deleted!");
        }
        if ($applicationDetails->registrationStatus == 0) {                                             // Static
            throw new Exception("application is allready deactivated!");
        }
        if ($applicationDetails->apply_mode == $applyMode['1']) {
            if ($applicationDetails->citizen_id != $user->id) {
                throw new Exception("You'r not the user of this form!");
            }
        } else {
            if ($applicationDetails->user_id != $user->id) {
                throw new Exception("You'r not the user of this form!");
            }
        }

        if ($applicationDetails->renewal == 0) {
            $tranTypeId = $trantype['New_Apply'];
        }
        if ($applicationDetails->renewal == 1) {
            $tranTypeId = $trantype['Renewal'];
        }
        $transactionDetails = $mPetTran->getTranDetails($applicationId, $tranTypeId)->first();
        if (!is_null($transactionDetails)) {
            throw new Exception("invalid operation Transaction details exist for application!");
        }
        $chargePayment = $mPetRegistrationCharge->getChargesbyId($applicationId)->where('paid_status', 1)->first();
        if (!is_null($chargePayment)) {
            throw new Exception("Payment for Respective charges exist!");
        }
    }

    /**
     * | Serch the holding and the saf details
     * | Serch the property details for filling the water Application Form
     * | @param request
     * | 01
        | Serial No : 
     */
    public function getSafHoldingDetails(Request $request)
    {

        $validated = Validator::make(
            $request->all(),
            [
                'connectionThrough' => 'required|int|in:1,2',
                'id'                => 'required|',
                'ulbId'             => 'required|'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mPropProperty          = new PropProperty();
            $mPropOwner             = new PropOwner();
            $mPropFloor             = new PropFloor();
            $mPropActiveSafOwners   = new PropActiveSafsOwner();
            $mPropActiveSafsFloor   = new PropActiveSafsFloor();
            $mPropActiveSaf         = new PropActiveSaf();
            $key                    = $request->connectionThrough;
            $refTenanted            = Config::get('property.OCCUPANCY_TYPE.TENANTED');

            switch ($key) {
                case (1):
                    $application = collect($mPropProperty->getPropByHolding($request->id, $request->ulbId));
                    $checkExist = collect($application)->first();
                    if (!$checkExist) {
                        throw new Exception("Data According to Holding Not Found!");
                    }
                    if (is_null($application['new_ward_mstr_id']) && is_null($application['new_ward_no'])) {
                        $owners['wardDetails'] = [
                            "wardId" => $application['ward_mstr_id'],
                            "wardNo" => $application['old_ward_no']
                        ];
                    } else {
                        $owners['wardDetails'] = [
                            "wardId" => $application['new_ward_mstr_id'],
                            "wardNo" => $application['new_ward_no']
                        ];
                    }
                    # collecting all data of owner and occupency
                    $occupancyOwnerType = collect($mPropFloor->getOccupancyType($application['id'], $refTenanted));
                    $owners['owners'] = collect($mPropOwner->getOwnerByPropId($application['id']));

                    # merge all data for return 
                    $details = $application->merge($owners)->merge($occupancyOwnerType);
                    return responseMsgs(true, "related Details!", $details, "", "", "", "POST", "");
                    break;

                case (2):
                    $application = collect($mPropActiveSaf->getSafDtlBySafUlbNo($request->id, $request->ulbId));
                    $checkExist = collect($application)->first();
                    if (!$checkExist) {
                        throw new Exception("Data According to SAF Not Found!");
                    }
                    if (is_null($application['new_ward_mstr_id']) && is_null($application['new_ward_no'])) {
                        $owners['wardDetails'] = [
                            "wardId" => $application['ward_mstr_id'],
                            "wardNo" => $application['old_ward_no']
                        ];
                    } else {
                        $owners['wardDetails'] = [
                            "wardId" => $application['new_ward_mstr_id'],
                            "wardNo" => $application['new_ward_no']
                        ];
                    }
                    # collecting all data 
                    $occupancyOwnerType         = collect($mPropActiveSafsFloor->getOccupancyType($application['id'], $refTenanted));
                    $owners['owners']           = collect($mPropActiveSafOwners->getOwnerDtlsBySafId($application['id']));

                    # merge all data for return 
                    $details = $application->merge($owners)->merge($occupancyOwnerType);
                    return responseMsgs(true, "related Details!", $details, "", "", "", "POST", "");
                    break;
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * | Logged in citizen Holding & Saf
     */
    public function citizenHoldingSaf(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'type'  => 'required|In:holding,saf,ptn',
                'ulbId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $citizenId                  = authUser($req)->id;
            $ulbId                      = $req->ulbId;
            $type                       = $req->type;
            $mPropActiveSafs            = new PropActiveSaf();
            $mPropProperty              = new PropProperty();
            $mActiveCitizenUndercare    = new ActiveCitizenUndercare();
            $caretakerProperty          = $mActiveCitizenUndercare->getTaggedPropsByCitizenId($citizenId);

            if ($type == 'saf') {
                $data = $mPropActiveSafs->getCitizenSafs($citizenId, $ulbId);
                $msg = 'Citizen Safs';
            }

            if ($type == 'holding') {
                $data = $mPropProperty->getCitizenHoldings($citizenId, $ulbId);
                if ($caretakerProperty->isNotEmpty()) {
                    $propertyId = collect($caretakerProperty)->pluck('property_id');
                    $data2 = $mPropProperty->getNewholding($propertyId);
                    $data = $data->merge($data2);
                }
                $data = collect($data)->map(function ($value) {
                    if (!is_null($value['new_holding_no']) || !is_null($value['holding_no'])) {
                        return $value;
                    }
                })->filter()->values();
                $msg = 'Citizen Holdings';
            }
            if ($data->isEmpty())
                throw new Exception('No Data Found');

            return responseMsgs(true, $msg, remove_null($data), '010801', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }


    /**
     * | Show approved appliction 
        | Demo Remove
     */
    public function getApproveRegistration(Request $req)
    {
        try {
            $user                   = authUser($req);
            $confUserType           = $this->_userType;
            $confDbKey              = $this->_dbKey;
            $mPetActiveRegistration = new PetActiveRegistration();

            if ($user->user_type != $confUserType['1']) {                                       // If not a citizen
                throw new Exception("You are not an autherised Citizen!");
            }
            # Collect querry Exceptions 
            try {
                $refAppDetails = $mPetActiveRegistration->dummyApplicationDetails($user->id, $confDbKey['1'])
                    ->select(
                        DB::raw("REPLACE(pet_active_registrations.application_type, '_', ' ') AS ref_application_type"),
                        DB::raw("TO_CHAR(pet_active_registrations.application_apply_date, 'DD-MM-YYYY') as ref_application_apply_date"),
                        "pet_active_registrations.*",
                        "pet_active_applicants.applicant_name",
                    )
                    ->orderByDesc('pet_active_registrations.id')
                    ->get();
            } catch (QueryException $q) {
                return responseMsgs(false, "An error occurred during the query!", $q->getMessage(), "", "01", ".ms", "POST", $req->deviceId);
            }
            return responseMsgs(true, "list of active registration!", remove_null($refAppDetails), "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }


    /**
     * | Edit the application pet details
        | Serial No :
        | Create a log for the old record 
        | Redesign the function 
        | CAUTION
     */
    public function editPetDetails(PetEditReq $req)
    {
        try {
            $applicationId      = $req->id;
            $confTableName      = $this->_tableName;
            $mPetActiveDetail   = new PetActiveDetail();
            $mPetAudit          = new PetAudit();
            $this->checkParamForPetUdate($req);

            DB::beginTransaction();
            $petDetails = $mPetActiveDetail->getPetDetailsByApplicationId($applicationId)->first();
            $oldPetDetails = json_encode($petDetails);
            $mPetAudit->saveAuditData($oldPetDetails, $confTableName['1']);
            $mPetActiveDetail->updatePetDetails($req, $petDetails);
            DB::commit();

            return responseMsgs(true, "Pet Details Updated!", [], "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Check Param for update the pet Application details 
        | Serial No : 
     */
    public function checkParamForPetUdate($req)
    {
        $user                   = authUser($req);
        $applicationId          = $req->id;
        $confRoles              = $this->_petWfRoles;
        $mPetActiveRegistration = new PetActiveRegistration();
        $mWfRoleusermap         = new WfRoleusermap();
        $mPetTran               = new PetTran();

        $applicationdetails = $mPetActiveRegistration->getPetApplicationById($applicationId)->first();
        if (!$applicationdetails) {
            throw new Exception("Application details not found!");
        }
        switch ($applicationdetails) {
            case (is_null($applicationdetails->citizen_id) && !is_null($applicationdetails->user_id)):
                $getRoleReq = new Request([                                                 // make request to get role id of the user
                    'userId'        => $user->id,
                    'workflowId'    => $applicationdetails->workflow_id
                ]);
                $readRoleDtls = $mWfRoleusermap->getRoleByUserWfId($getRoleReq);
                if (!$readRoleDtls) {
                    throw new Exception("User Dont have any role!");
                }
                $roleId = $readRoleDtls->wf_role_id;
                if ($roleId != $confRoles['BO']) {
                    throw new Exception("You are not Permited to edit the application!");
                }
                if ($user->id != $applicationdetails->user_id) {
                    throw new Exception("You are not the right user who applied!");
                }
                if ($roleId != $applicationdetails->initiator_role_id) {
                    throw new Exception("You are not the Initiator!");
                }
                break;

            case (is_null($applicationdetails->user_id)):
                if ($user->id != $applicationdetails->citizen_id) {
                    throw new Exception("You are not the right user who applied!");
                }
                if ($applicationdetails->payment_status == 1) {
                    throw new Exception("Payment is done applicationcannot be updated!");
                }
                $transactionDetails = $mPetTran->getTranByApplicationId($applicationId)->first();
                if ($transactionDetails) {
                    throw new Exception("Transaction data exist application cannot be updated!");
                }
                break;
        }
        return [
            "applicationDetails" => $applicationdetails,
        ];
    }


    /**
     * | Edit applicant Details 
     */
    public function editApplicantDetails(Request $req)
    {
        try {
        } catch (Exception $e) {
            return responseMsgs(false, "Applicant Details Updated!", [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }


    /**
     * | Apply the renewal for pet 
     * | registered pet renewal process
        | Serial No :
        | Under Con 
     */
    public function applyPetRenewal(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'registrationId'    => 'required|In:holding,saf,ptn',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user       = authUser($request);
            $renewal    = 1;

            # Check the Registered Application existence
            $mPetApprovedRegistration = new PetApprovedRegistration();
            $refApprovedDetails = $mPetApprovedRegistration->getApplictionByRegId($request->registrationId)->first();
            if (!$refApprovedDetails) {
                throw new Exception("Application Detial Not found!");
            }

            # Check Params for renewal of Application
            $this->checkParamForRenewal();
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $req->deviceId);
        }
    }
}

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
use App\Models\Workflows\WfRole;

class AgencyNewController extends Controller
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
    protected $_tempId;
    protected $_paramTempId;

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
        $this->_moduleId = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
        $this->_docCode = Config::get('workflow-constants.AGENCY_DOC_CODE');
        $this->_tempParamId = Config::get('workflow-constants.TEMP_AG_ID');
        $this->_tempId = Config::get('workflow-constants.TEMP_ID');
        $this->_paramTempId = Config::get('workflow-constants.HOARD_ID');             //for hoarding Id
        $this->_paramId = Config::get('workflow-constants.AGY_ID');
        $this->_baseUrl = Config::get('constants.BASE_URL');
        $this->_docUrl = Config::get('workflow-constants.DOC_URL');
        $this->_fileUrl = Config::get('workflow-constants.FILE_URL');
        $this->_userType            = Config::get("workflow-constants.REF_USER_TYPE");
        $this->_docReqCatagory      = Config::get("workflow-constants.DOC_REQ_CATAGORY");
        // $this->Repository = $agency_repo;

        $this->_wfMasterId = Config::get('workflow-constants.AGENCY_WF_MASTER_ID');
    }
    /**
     * | Store  for agency 
     * | @param StoreRequest Request
     * | Function - 01
     * | API - 01
     */
    public function addNewAgency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agencyName' => 'required|',
            'correspondingAddress' => 'required|',
            'mobileNo' => 'required|numeric|digits:10',
            'email' => 'nullable|email',
            'contactPerson' => 'nullable',
            'gstNo' => 'nullable|',
            'panNo' => 'nullable|',
            'profile' => 'nullable|',
            "ulbId"   => 'nullable'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $ulbId = $request->ulbId ?? 2;
            $idGeneration       = new PrefixIdGenerator($this->_tempId, $ulbId);
            $applicationNo      = $idGeneration->generate();
            $applicationNo      = str_replace('/', '-', $applicationNo);
            DB::beginTransaction();
            $metaRequest = [
                'agency_name'             => $request->agencyName,
                'agency_code'             => $applicationNo,
                'corresponding_address'   => $request->correspondingAddress,
                'mobile'                  => $request->mobileNo,
                'email'                   => $request->email,
                'contact_person'          => $request->contactPerson,
                'gst_no'                  => $request->gstNo,
                'pan_no'                  => $request->panNo
            ];
            $agencyId = $this->_modelObj->createData($metaRequest);
            DB::commit();
            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'agecnyId' => $agencyId], "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**\
     * get all agency data 
     */
    public function getAll(Request $request)
    {
        try {
            $getAll = $this->_modelObj->getaLL();
            return responseMsgs(true, "get agency succesfully!!", ['data' => $getAll], "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**\
     * edit agencgy details
     */
    public function updateAgencydtl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agencyName'           => 'nullable|',
            'agencyCode'           => 'nullable|',
            'correspondingAddress' => 'nullable|',
            'mobileNo'             => 'nullable|numeric|digits:10',
            'email'                => 'nullable|email',
            'contactPerson'        => 'nullable',
            'gstNo'                => 'nullable|',
            'panNo'                => 'nullable|',
            'profile'              => 'nullable|',
            'UserId'               => 'required'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $agencyId     = $request->UserId;
            $checkAgency = $this->_modelObj->checkAgencyById($agencyId);
            if (!$checkAgency) {
                throw new Exception('agency not found !');
            }

            DB::beginTransaction();
            $metaRequest = [
                'agency_name'             => $request->agencyName,
                'agency_code'             => $request->agencyCode,
                'corresponding_address'   => $request->correspondingAddress,
                'mobile'                  => $request->mobileNo,
                'email'                   => $request->email,
                'contact_person'          => $request->contactPerson,
                'gst_no'                  => $request->gstNo,
                'pan_no'                  => $request->panNo
                // 'profile'                 => $request->agencyCode,
            ];
            $this->_modelObj->updateAgencydtl($metaRequest, $agencyId);
            DB::commit();

            return responseMsgs(true, "update agency dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**
     * soft delete 
     */
    public function AgencyDelete(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'       => 'required'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $agencyId  = $req->id;
            $checkAgency = $this->_modelObj->checkAgencyById($agencyId);               // check agency exist 
            if (!$checkAgency) {
                throw new Exception('agency not found !');
            }
            DB::beginTransaction();
            $this->_modelObj->updateStatus($agencyId);
            DB::commit();
            return responseMsgs(true, "delete agency dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /****=========================================**/
    #  here  for hoarding  
    public function addNewHoarding(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'hoardingType' => 'required|',
            'latitude' => 'nullable|',
            'longitude' => 'nullable|',
            'length' => 'required|',
            'width' => 'nullable|numeric',
            'remarks' => 'nullable',
            'address' => 'nullable|',
            'agencyId' => 'nullable|',
            'documents' => 'nullable',
            "zoneId" => 'required',
            "wardId" => 'required'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $ulbId = $req->ulbId ?? 2;
            $idGeneration       = new PrefixIdGenerator( $this->_paramTempId, $ulbId);
            $applicationNo      = $idGeneration->generate();
            $applicationNo      = str_replace('/', '-', $applicationNo);
            DB::beginTransaction();
            $metaReqs = [
                'hoarding_no' => $applicationNo,
                'hoarding_type_id' => $req->hoardingType,
                'latitude' => $req->latitude,
                'longitude' => $req->longitude,
                'length' => $req->length,
                'width' => $req->width,
                'agency_id' => $req->agencyId,
                'address' => $req->address,
                'remarks' => $req->remarks,
                "zone_id" => $req->zoneId,
                "ward_id" => $req->wardId
            ];
            $hoarding = $this->_hoarObj->creteData($metaReqs);

            DB::commit();

            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'ApplicationNo' => $hoarding], "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**
     * function for get hoarding details 
     */

    public function getAllHoard(Request $request)
    {
        try {
            $getAll = $this->_hoarObj->getaLLHording();
            return responseMsgs(true, "get hoarding succesfully!!", ['data' => $getAll], "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**\
     * edit hoarding details
     */
    public function updatehoardingdtl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hoardingType' => 'nullable|',
            'length' => 'nullable|',
            'width' => 'nullable|',
            'remarks' => 'nullable',
            'address' => 'nullable|',
            'agencyId' => 'nullable|',
            'documents' => 'nullable',
            'userId' => 'required'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $hoardingId     = $request->userId;
            $checkHoard =  $this->_hoarObj->checkHoardById($hoardingId);
            if (!$checkHoard) {
                throw new Exception('hoarding not found !');
            }
            DB::beginTransaction();
            $metaRequest = [
                'hoarding_no' => $request->hoardingNo,
                'hoarding_type' => $request->hoardingType,
                'length' => $request->length,
                'width' => $request->width,
                'agency_id' => $request->agencyId,
                'address' => $request->address,
                'remarks' => $request->remarks,
            ]; // 'profile'                 => $request->agencyCode,
            $this->_hoarObj->updateHoarddtl($metaRequest, $hoardingId);
            DB::commit();

            return responseMsgs(true, "update agency dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**
     * soft delete 
     */
    public function HoardDelete(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'       => 'required'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $hoardId  = $req->id;
            $checkAgency = $this->_hoarObj->checkHoardById($hoardId);               // check agency exist 
            if (!$checkAgency) {
                throw new Exception('agency not found !');
            }
            DB::beginTransaction();
            $this->_hoarObj->updateStatus($hoardId);
            DB::commit();
            return responseMsgs(true, "delete agency dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**=================================================================== */
    /**
     * here for brand master
     */
    public function addBrand(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "brandType" => "required"
        ]);

        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            DB::beginTransaction();
            $metaReqs = [
                'brand_type' => $req->brandType,

            ];
            $hoarding = $this->_brandObj->creteData($metaReqs);

            DB::commit();

            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'ApplicationNo' => $hoarding], "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    # get all brand 
    public function getAllBrand(Request $request)
    {
        try {
            $getAll = $this->_brandObj->getaLLbrand();
            return responseMsgs(true, "Brand details!!", ['data' => $getAll], "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**\
     * edit hoarding details
     */
    public function updateBrand(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "userId" => 'required',
            "brandType" => 'nullable'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $brandId     = $request->userId;
            $checkHoard =  $this->_brandObj->checkBrandById($brandId);
            if (!$checkHoard) {
                throw new Exception('hoarding not found !');
            }
            DB::beginTransaction();
            $metaRequest = [
                'brand_type' => $request->brandType,
            ];
            $this->_brandObj->updatedtl($metaRequest, $brandId);
            DB::commit();

            return responseMsgs(true, "update agency dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**
     * soft delete 
     */
    public function DeleteBrand(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'       => 'required'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $brandId  = $req->id;
            $checkBrand = $this->_brandObj->checkBrandById($brandId);               // check brand exist 
            if (!$checkBrand) {
                throw new Exception('brand not found !');
            }
            DB::beginTransaction();
            $this->_brandObj->updateStatus($brandId);
            DB::commit();
            return responseMsgs(true, "delete brand dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**========================================================== */
    # this function for Advertisement type 
    public function addAdvertisementType(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "advertisementType" => "required"
        ]);

        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            DB::beginTransaction();
            $metaReqs = [
                'type' => $req->advertisementType,

            ];
            $hoarding =  $this->_advertObj->creteData($metaReqs);

            DB::commit();

            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'ApplicationNo' => $hoarding], "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    # get all advertisement

    public function getAllAdvertisement(Request $request)
    {
        try {
            $getAll = $this->_advertObj->getAllDtls();
            return responseMsgs(true, "advertisements details!!", ['data' => $getAll], "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**\
     * edit hoarding details
     */
    public function updateAdvertisemnet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "userId" => 'required',
            "advertisementType" => 'nullable'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $advertId     = $request->userId;
            $checkHoard    =    $this->_advertObj->checkBrandById($advertId);
            if (!$checkHoard) {
                throw new Exception('hoarding not found !');
            }
            DB::beginTransaction();
            $metaRequest = [
                'type' => $request->advertisementType,
            ];
            $this->_advertObj->updatedtl($metaRequest, $advertId);
            DB::commit();

            return responseMsgs(true, "update agency dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**
     * soft delete 
     */
    public function deactiveAdvertisement(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'       => 'required'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $advertId  = $req->id;
            $checkBrand =  $this->_advertObj->checkdtlsById($advertId);               // check brand exist 
            if (!$checkBrand) {
                throw new Exception('brand not found !');
            }
            DB::beginTransaction();
            $this->_advertObj->updateStatus($advertId);                                   // handle status to deactive 
            DB::commit();
            return responseMsgs(true, "delete brand dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /*============================================================================ */
    # start for locations master
    public function addNewLocations(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "location" => "required"
        ]);

        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            DB::beginTransaction();
            $metaReqs = [
                'location' => $req->location,

            ];
            $hoarding =  $this->_locatObj->creteData($metaReqs);

            DB::commit();

            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'ApplicationNo' => $hoarding], "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    # get all details 
    public function getAllDtls(Request $request)
    {
        try {
            $getAll = $this->_locatObj->getAllDtls();
            return responseMsgs(true, "advertisements details!!", ['data' => $getAll], "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    public function updatedtl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "userId" => 'required',
            "location" => 'nullable'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $locatId     = $request->userId;
            $checkHoard    = $this->_locatObj->checkdtlsById($locatId);
            if (!$checkHoard) {
                throw new Exception(' data not found !');
            }
            DB::beginTransaction();
            $metaRequest = [
                'location' => $request->location,
            ];
            $this->_locatObj->updatedtl($metaRequest, $locatId);
            DB::commit();

            return responseMsgs(true, "update agency dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**
     * soft delete 
     */
    public function deactiveLocation(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'       => 'required'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $locatId   = $req->id;
            $checkBrand = $this->_locatObj->checkdtlsById($locatId);               // check brand exist 
            if (!$checkBrand) {
                throw new Exception('data not found !');
            }
            DB::beginTransaction();
            $this->_locatObj->updateStatus($locatId);                                   // handle status to deactive 
            DB::commit();
            return responseMsgs(true, "delete brand dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**======================================================================= */
    # advertiser crud
    public function addNewAdvertiser(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "advertiserName" => "required",
            "address"        => "required",
            "company"        => "required",
            "officeAddress"  => "required",
            "contactPerson"  => "required",
            "mobileNo"       => "required",
            "email"          => "nullable",
            "idProof"       => "nullable"
        ]);

        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            DB::beginTransaction();
            $metaReqs = [
                "advertiser_name" => $req->advertiserName,
                "address" => $req->address,
                "company" => $req->company,
                "office_address" => $req->officeAddress,
                "contact_person" => $req->contactPerson,
                "email" => $req->email,
                "mobile_no" => $req->mobileNo,
                "id_proof" => $req->idProof
            ];
            $hoarding =  $this->_advObj->creteData($metaReqs);
            DB::commit();
            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'ApplicationNo' => $hoarding], "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    // # get all details 
    public function getALLAdvertiser(Request $request)
    {
        try {
            $getAll = $this->_advObj->getAllDtls();
            return responseMsgs(true, "advertiser details!!", ['data' => $getAll], "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    # update details of advertiser
    public function updateAdvertiserdtl(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'userId'         => 'required',
            'advertiserName' => 'nullable',
            'address'        => 'nullable',
            'company'        => 'nullable',
            'officeAddress'  => 'nullable',
            'contactPerson'  => 'nullable',
            'mobileNo'       => 'nullable|numeric|digits:10',
            'email'          => 'nullable|email',
            'idProof'        => 'nullable',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $advId     = $req->userId;
            $checkHoard    = $this->_advObj->checkdtlsById($advId);
            if (!$checkHoard) {
                throw new Exception(' data not found !');
            }
            DB::beginTransaction();
            $metaReqs = [
                "advertiser_name" => $req->advertiserName,
                "address" => $req->address,
                "company" => $req->company,
                "office_address" => $req->officeAddress,
                "contact_person" => $req->contactPerson,
                "email" => $req->email,
                "mobile_no" => $req->mobileNo,
                "id_proof" => $req->idProof
            ];
            $this->_advObj->updatedtl($metaReqs, $advId);
            DB::commit();

            return responseMsgs(true, "update agency dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**
     * soft delete 
     */
    public function deactiveAdvertiser(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'       => 'required'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $locatId   = $req->id;
            $checkBrand =  $this->_advObj->checkdtlsById($locatId);               // check advertiser exist 
            if (!$checkBrand) {
                throw new Exception('data not found !');
            }
            DB::beginTransaction();
            $this->_advObj->updateStatus($locatId);                                   // handle status to deactive 
            DB::commit();
            return responseMsgs(true, "delete advertiser  dtl succesfully !!",  "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**
     * | 
     * | @param request
     * | @var 
       
     */
    public function applyHoarding(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'agencyId'             => 'required',
                'hoardingId'           => 'required',
                'agencyName'           => "nullable|",
                'hoardingType'         => "nullable|",
                'allotmentDate'        => "nullable",
                'advertiser'           => "required",
                'from'                 => "nullable",
                'to'                   => "nullable",
                "rate"                 => "nullable",
                'fatherName'           => "nullable",
                "email"                => 'nullable',
                'residenceAddress'     => 'nullable',
                'workflowId'           => 'nullable',
                'documents' => 'required|array',
                'documents.*.image' => 'required|mimes:png,jpeg,pdf,jpg',
                'documents.*.docCode' => 'required|string',
                'documents.*.ownerDtlId' => 'nullable|integer'

            ]
        );
        if ($validated->fails())
            return validationError($validated);
        // return $request->all();

        try {
            $user                           = authUser($request);
            $refRequest                     = array();
            $mDocuments                     = $request->documents;
            $ulbWorkflowObj                 = new WfWorkflow();
            $mWorkflowTrack                 = new WorkflowTrack();
            $refUserType                    = Config::get('workflow-constants.REF_USER_TYPE');
            $refApplyFrom                   = Config::get('workflow-constants.APP_APPLY_FROM');
            $refWorkflow                    = Config::get('workflow-constants.ADVERTISEMENT-HOARDING');
            $confModuleId                   = Config::get('workflow-constants.ADVERTISMENT_MODULE');
            $refConParamId                  = Config::get('waterConstaint.PARAM_IDS');
            $advtRole                       = Config::get("workflow-constants.ROLE-LABEL");
            $var = $request->hoardingId;
            $this->checkHoardingParams($request);                                        //check alloted date 
            $ulbId      = $request->ulbId ?? 2;
            # Get initiater and finisher
            $ulbWorkflowId = $ulbWorkflowObj->getulbWorkflowId($refWorkflow, $ulbId);
            if (!$ulbWorkflowId) {
                throw new Exception("Respective Ulb is not maped to Water Workflow!");
            }
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);
            $refFinisherRoleId  = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId     = DB::select($refFinisherRoleId);
            $initiatorRoleId    = DB::select($refInitiatorRoleId);
            if (!$finisherRoleId || !$initiatorRoleId) {
                throw new Exception("initiatorRoleId or finisherRoleId not found for respective Workflow!");
            }

            # If the user is not citizen
            if ($user->user_type != $refUserType['1']) {
                $request->request->add(['workflowId' => $refWorkflow]);
                $roleDetails = $this->getRole($request);
                if (!$roleDetails) {
                    throw new Exception("Role detail Not found!");
                }
                // $roleId = $roleDetails['wf_role_id'];
                $refRequest = [
                    "applyFrom" => $user->user_type,
                    "empId"     => $user->id
                ];
            } else {
                $refRequest = [
                    "applyFrom" => $refApplyFrom['1'],
                    "citizenId" => $user->id
                ];
            }

            # Get chrages for deactivation

            $refRequest["initiatorRoleId"]   = collect($initiatorRoleId)->first()->role_id;
            $refRequest["finisherRoleId"]    = collect($finisherRoleId)->first()->role_id;
            $refRequest["roleId"]            = $roleId ?? null;
            $refRequest["ulbWorkflowId"]     = $ulbWorkflowId->id;
            $refRequest['userType']          = $user->user_type;

            DB::beginTransaction();
            $idGeneration       = new PrefixIdGenerator($this->_tempParamId, $ulbId);
            $applicationNo      = $idGeneration->generate();
            $applicationNo      = str_replace('/', '-', $applicationNo);
            // $mWfWorkflow=new WfWorkflow();
            // $WfMasterId = ['WfMasterId' =>  $this->_wfMasterId];
            // $request->request->add($WfMasterId);
            $AgencyId        =  $this->_agencyObj->saveRequestDetails($request, $refRequest, $applicationNo, $ulbId);
            $var = [
                'relatedId' => $AgencyId,
                "Status"    => 2,

            ];
            $this->uploadHoardDocument($AgencyId, $mDocuments, $request->auth);
            $this->_agencyObj->updateUploadStatus($AgencyId, true);                       //update status when doc upload 
            # save for  work flow track
            if ($user->user_type == "Citizen") {                                                        // Static
                $receiverRoleId = $advtRole['DA'];
            }
            if ($user->user_type != "Citizen") {                                                        // statis
                $receiverRoleId = collect($initiatorRoleId)->first()->role_id;
            }

            $metaReqs['citizenId'] =  $refRequest['citizenId'] ?? null;
            $metaReqs['moduleId'] =  14;
            $metaReqs['workflowId'] =  $ulbWorkflowId['id'];
            $metaReqs['refTableDotId'] = 'agency_hoardings.id';
            $metaReqs['refTableIdValue'] = $var['relatedId'];
            $metaReqs['senderRoleId'] = $senderRoleId ?? null;
            $metaReqs['receiverRoleId'] = $receiverRoleId ?? null;
            $metaReqs['user_id'] = $user->id;
            $metaReqs['trackDate'] = Carbon::now()->format('Y-m-d H:i:s');
            $request->request->add($metaReqs);
            // dd($metaReqs)\;
            $mWorkflowTrack->saveTrack($request);
            DB::commit();
            return responseMsgs(true, "applications apply sucesfully !", $applicationNo, "", "02", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }
    /**
     * check hoarding is already applied or not 
     * between thier respective date 
     */
    public function checkHoardingParams($request)
    {
        $currentDate = Carbon::now();
        $result =  $this->_agencyObj->checkHoarding($request);
        if ($result->approve !== 2) {
            $toDate = Carbon::parse($result->to_date);

            if ($currentDate->lessThan($toDate)) {
                throw new \Exception("This Hoarding Is Allotted Till Date: {$toDate->format('d-m-Y')}");
            }   
            return responseMsgs(true, "Agency Details", $result, "050502", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        }
    }
    /*
     * upload Document By agency At the time of Registration
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadHoardDocument($AgencyId, $documents, $auth)
    {
        $docUpload = new DocumentUpload;
        $mWfActiveDocument = new WfActiveDocument();
        $mAgencyHoarding = new AgencyHoarding();
        $relativePath   = Config::get('constants.AGENCY_ADVET.RELATIVE_PATH');

        collect($documents)->map(function ($doc) use ($AgencyId, $docUpload, $mWfActiveDocument, $mAgencyHoarding, $relativePath, $auth) {
            $metaReqs = array();
            $getApplicationDtls = $mAgencyHoarding->getApplicationDtls($AgencyId);
            $refImageName = $doc['docCode'];
            $refImageName = $getApplicationDtls->id . '-' . $refImageName;
            $documentImg = $doc['image'];
            $imageName = $docUpload->upload($refImageName, $documentImg, $relativePath);
            $metaReqs['moduleId'] = Config::get('workflow-constants.ADVERTISMENT_MODULE');
            $metaReqs['activeId'] = $getApplicationDtls->id;
            $metaReqs['workflowId'] = $getApplicationDtls->workflow_id;
            $metaReqs['ulbId'] = $getApplicationDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document'] = $imageName;
            $metaReqs['docCode'] = $doc['docCode'];
            $metaReqs['ownerDtlId'] = $doc['ownerDtlId'];
            $a = new Request($metaReqs);
            // $mWfActiveDocument->postDocuments($a, $auth);
            $metaReqs =  $mWfActiveDocument->metaReqs($metaReqs);
            $mWfActiveDocument->create($metaReqs);
            // foreach ($metaReqs as $key => $val) {
            //     $mWfActiveDocument->$key = $val;
            // }
            // $mWfActiveDocument->save();
        });
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
     * |----------------------------- Read the server url ------------------------------|
        | Serial No : 
     */
    public function readDocumentPath($path)
    {
        $path = (config('app.url') . "/" . $path);
        return $path;
    }

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
                // $userId = $user->id;
                // $ulbId = $applicantDetals->ulb_id;
                // $role = $this->getUserRoll($userId, $ulbId, $refWorkFlowMaster);
                // if (is_null($role)) {
                //     throw new Exception("You dont have any role!");
                // }
                // if ($role->can_upload_document != true) {
                //     throw new Exception("You dont have permission to upload Document!");
                // }
                break;
        }
    }
    public function getUserRoll($user_id, $ulb_id, $workflow_id)
    {
        try {
            // DB::enableQueryLog();
            $data = WfRole::select(
                DB::raw(
                    "wf_roles.id as role_id,wf_roles.role_name,
                                            wf_workflowrolemaps.is_initiator, wf_workflowrolemaps.is_finisher,
                                            wf_workflowrolemaps.forward_role_id,forword.role_name as forword_name,
                                            wf_workflowrolemaps.backward_role_id,backword.role_name as backword_name,
                                            wf_workflowrolemaps.allow_full_list,wf_workflowrolemaps.can_escalate,
                                            wf_workflowrolemaps.serial_no,wf_workflowrolemaps.is_btc,
                                            wf_workflowrolemaps.can_upload_document,
                                            wf_workflowrolemaps.can_verify_document,
                                            wf_workflowrolemaps.can_backward,
                                            wf_workflows.id as workflow_id,wf_masters.workflow_name,
                                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,
                                            ulb_masters.ulb_type"
                )
            )
                ->join("wf_roleusermaps", function ($join) {
                    $join->on("wf_roleusermaps.wf_role_id", "=", "wf_roles.id")
                        ->where("wf_roleusermaps.is_suspended", "=", FALSE);
                })
                ->join("users", "users.id", "=", "wf_roleusermaps.user_id")
                ->join("wf_workflowrolemaps", function ($join) {
                    $join->on("wf_workflowrolemaps.wf_role_id", "=", "wf_roleusermaps.wf_role_id")
                        ->where("wf_workflowrolemaps.is_suspended", "=", FALSE);
                })
                ->leftjoin("wf_roles AS forword", "forword.id", "=", "wf_workflowrolemaps.forward_role_id")
                ->leftjoin("wf_roles AS backword", "backword.id", "=", "wf_workflowrolemaps.backward_role_id")
                ->join("wf_workflows", function ($join) {
                    $join->on("wf_workflows.id", "=", "wf_workflowrolemaps.workflow_id")
                        ->where("wf_workflows.is_suspended", "=", FALSE);
                })
                ->join("wf_masters", function ($join) {
                    $join->on("wf_masters.id", "=", "wf_workflows.wf_master_id")
                        ->where("wf_masters.is_suspended", "=", FALSE);
                })
                ->join("ulb_masters", "ulb_masters.id", "=", "wf_workflows.ulb_id")
                ->where("wf_roles.is_suspended", false)
                ->where("wf_roleusermaps.user_id", $user_id)
                ->where("wf_workflows.ulb_id", $ulb_id)
                ->where("wf_workflows.wf_master_id", $workflow_id)
                ->orderBy("wf_roleusermaps.id", "desc")
                ->first();
            // dd(DB::getQueryLog());
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function checkFullDocUpload($req)
    {
        # Check the Document upload Status
        $confDocReqCatagory = $this->_docReqCatagory;
        $documentList = $this->getDocList($req);
        $refDoc = collect($documentList)['original']['data']['listDocs'];
        $checkDocument = collect($refDoc)->map(function ($value)
        use ($confDocReqCatagory) {
            if ($value['docType'] == $confDocReqCatagory['1'] || $value['docType'] == $confDocReqCatagory['2']) {
                $doc = collect($value['uploadedDoc'])->first();
                if (is_null($doc)) {
                    return true;
                }
                return true;
            }
            return true;
        });
        return $checkDocument;
    }
}

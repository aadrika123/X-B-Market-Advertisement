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
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Models\User;

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

    public function __construct(iSelfAdvetRepo $agency_repo)
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
        $this->_tempParamId = Config::get('workflow-constants.TEMP_AGY_ID');
        $this->_paramId = Config::get('workflow-constants.AGY_ID');
        $this->_baseUrl = Config::get('constants.BASE_URL');
        $this->_fileUrl = Config::get('workflow-constants.FILE_URL');
        $this->Repository = $agency_repo;

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
            'agencyCode' => 'required|',
            'correspondingAddress' => 'required|',
            'mobileNo' => 'required|numeric|digits:10',
            'email' => 'nullable|email',
            'contactPerson' => 'nullable',
            'gstNo' => 'nullable|',
            'panNo' => 'nullable|',
            'profile' => 'nullable|',
            // 'documents' => 'required|array',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $document = $request->profile;
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
            $agencyId = $this->_modelObj->createData($metaRequest);
            DB::commit();
            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'agecnyId' => $agencyId], "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    /**
     * | Upload document after application is submit
     */
    public function uploadDocument($tempId, $documents, $auth)
    {
        collect($documents)->map(function ($doc) use ($tempId, $auth) {
            $metaReqs = array();
            $docUpload = new DocumentUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mAdvActiveAgency = new AgencyMaster();
            $relativePath = Config::get('constants.AGENCY_ADVET.RELATIVE_PATH');
            $getApplicationDtls = $mAdvActiveAgency->checkAgencyById($tempId);
            $refImageName = $doc['docCode'];
            $refImageName = $getApplicationDtls->id . '-' . $refImageName;
            $documentImg = $doc['image'];
            $imageName = $docUpload->upload($refImageName, $documentImg, $relativePath);

            $metaReqs['moduleId'] = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
            $metaReqs['activeId'] = $getApplicationDtls->id;
            $metaReqs['workflowId'] = $getApplicationDtls->workflow_id;
            $metaReqs['ulbId'] = $getApplicationDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document'] = $imageName;
            $metaReqs['docCode'] = $doc['docCode'];
            $metaReqs['ownerDtlId'] = $doc['ownerDtlId'];
            $a = new Request($metaReqs);
            // $mWfActiveDocument->postDocuments($a,$auth);
            $metaReqs =  $mWfActiveDocument->metaReqs($metaReqs);
            // $mWfActiveDocument->create($metaReqs);
            foreach ($metaReqs as $key => $val) {
                $mWfActiveDocument->$key = $val;
            }
            $mWfActiveDocument->save();
        });
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
            return $agencyId     = $request->UserId;
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
            'hoardingNo' => 'required|',
            'hoardingType' => 'required|',
            'latitude' => 'required|',
            'longitude' => 'required|',
            'length' => 'required|',
            'width' => 'nullable|numeric',
            'remarks' => 'nullable',
            'locationId' => 'nullable|',
            'agencyId' => 'nullable|',
            'documents' => 'nullable',
        ]);

        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }

        try {


            DB::beginTransaction();
            $metaReqs = [
                'hoarding_no' => $req->hoardingNo,
                'hoarding_type_id' => $req->hoardingType,
                'latitude' => $req->latitude,
                'longitude' => $req->longitude,
                'length' => $req->length,
                'width' => $req->width,
                'agency_id' => $req->agencyId,
                'location_id' => $req->locationId,
                'remarks' => $req->remarks,
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
            return responseMsgs(true, "get HORDING succesfully!!", ['data' => $getAll], "050501", "1.0", responseTime(), 'POST', $request->deviceId ?? "");
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
            'hoardingNo' => 'nullable|',
            'hoardingType' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'remarks' => 'nullable',
            'locationId' => 'nullable|numeric',
            'agencyId' => 'nullable|numeric',
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
                'hoarding_type_id' => $request->hoardingType,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'length' => $request->length,
                'width' => $request->width,
                'agency_id' => $request->agencyId,
                'location_id' => $request->locationId,
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
        | Not Working
        | Serial No : 06
        | Differenciate btw citizen and user 
        | check if the ulb is same as the consumer details 
     */
    public function applyHoarding(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'agencyName'           => "nullable|",
                'hoardingType'         => "nullable|",
                'allotmentDate'        => "nullable",
                'from'                 => "nullable",
                'to'                   => "nullable",
                "rate"                 => "nullable",
                'fatherName'           => "nullable",
                "email"                => 'nullable',
                'residenceAddress'     => 'nullable',
                'workflowId'           => 'nullable'

            ]
        );
        if ($validated->fails())
            return validationError($validated);
        // return $request->all();

        try {
            $user                           = authUser($request);
            $refRequest                     = array();
            $ulbWorkflowObj                 = new WfWorkflow();
            $mWorkflowTrack                 = new WorkflowTrack();
            $refUserType                    = Config::get('workflow-constants.REF_USER_TYPE');
            $refApplyFrom                   = Config::get('workflow-constants.APP_APPLY_FROM');
            $refWorkflow                    = Config::get('workflow-constants.ADVERTISEMENT-HOARDING');
            $confModuleId                   = Config::get('workflow-constants.ADVERTISMENT_MODULE');
            $refConParamId                  = Config::get('waterConstaint.PARAM_IDS');
            $advtRole                    = Config::get("workflow-constants.ROLE-LABEL");

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
            $mAgency        =  $this->_agencyObj->saveRequestDetails($request, $refRequest, $applicationNo);
            $var = [
                'relatedId' => $mAgency->id,
                "Status"    => 2,

            ];
            # save for  work flow track
            if ($user->user_type == "Citizen") {                                                        // Static
                $receiverRoleId = $advtRole['DA'];
            }
            if ($user->user_type != "Citizen") {                                                        // Static
                $receiverRoleId = collect($initiatorRoleId)->first()->role_id;
            }
            // dd($mAgency);
            # Save data in track
             $metaReqs = new Request(
                [
                    'citizenId'         => $refRequest['citizenId'] ?? null,
                    'moduleId'          => 2,
                    'workflowId'        => $ulbWorkflowId['id'],
                    'refTableDotId'     => 'agency_hoardings.id',                                     // Static
                    'refTableIdValue'   => $var['relatedId'],
                    'user_id'           => $user->id,
                    'ulb_id'            => $ulbId,
                    'senderRoleId'      => $senderRoleId ?? null,
                    'receiverRoleId'    => $receiverRoleId ?? null
                ]
            );
           $mWorkflowTrack->saveTrack($metaReqs);
            DB::commit();
            return responseMsgs(true, "applications apply sucesfully !", $applicationNo, "", "02", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }
    // /**
    //  * 
    //  */
    // public function addNew($req)
    // {
    //     // Variable Initializing
    //     $bearerToken = $req->bearerToken();
    //     $LicencesMetaReqs = $this->MetaReqs($req);
    //     // $workflowId = $this->_workflowId;
    //     // $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);        // Workflow Trait Function
    //     $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);                 // Workflow Trait Function
    //     $ulbWorkflows = $ulbWorkflows['data'];
    //     // $ipAddress = getClientIpAddress();
    //     // $mLecenseNo = ['license_no' => 'LICENSE-' . random_int(100000, 999999)];                  // Generate Lecence No
    //     $ulbWorkflowReqs = [                                                                           // Workflow Meta Requests
    //         'workflow_id' => $ulbWorkflows['id'],
    //         'initiator_role_id' => $ulbWorkflows['initiator_role_id'],
    //         'last_role_id' => $ulbWorkflows['initiator_role_id'],
    //         'current_role_id' => $ulbWorkflows['initiator_role_id'],
    //         'finisher_role_id' => $ulbWorkflows['finisher_role_id'],
    //     ];

    //     // $LicencesMetaReqs=$this->uploadLicenseDocument($req,$LicencesMetaReqs);

    //     $LicencesMetaReqs = array_merge(
    //         [
    //             'ulb_id' => $req->ulbId,
    //             'citizen_id' => $req->citizenId,
    //             'application_date' => $this->_applicationDate,
    //             'ip_address' => $req->ipAddress,
    //             'application_type' => "New Apply"
    //         ],
    //         $this->MetaReqs($req),
    //         $ulbWorkflowReqs
    //     );


    //     $licenceId = AdvActiveHoarding::create($LicencesMetaReqs)->id;
    //     // $licenceId = 5;

    //     $mDocuments = $req->documents;
    //     // $mDocuments = str_replace(']"'," ",$mDocuments);
    //     // $this->uploadDocument($licenceId, $mDocuments, $req->auth);

    //     return $req->application_no;
    // }
    // public function MetaReqs($req)
    // {
    //     $metaReqs = [
    //         // 'zone_id' => $req->zoneId,
    //         // 'license_year' => $req->licenseYear,
            
    //         // 'typology' => $req->HordingType,               // Hording Type is Convert Into typology
    //         // 'display_location' => $req->displayLocation,
    //         // 'width' => $req->width,
    //         // 'length' => $req->length,
    //         // 'display_area' => $req->displayArea,
    //         // 'longitude' => $req->longitude,
    //         // 'latitude' => $req->latitude,
    //         // 'material' => $req->material,
    //         // 'illumination' => $req->illumination,
    //         // 'indicate_facing' => $req->indicateFacing,
    //         // 'property_type' => $req->propertyType,
    //         // 'display_land_mark' => $req->displayLandMark,
    //         // 'property_owner_name' => $req->propertyOwnerName,
    //         // 'property_owner_address' => $req->propertyOwnerAddress,
    //         // 'property_owner_city' => $req->propertyOwnerCity,
    //         // 'property_owner_whatsapp_no' => $req->propertyOwnerWhatsappNo,
    //         // 'property_owner_mobile_no' => $req->propertyOwnerMobileNo,
    //         // 'user_id' => $req->userId,
    //         'applicant_name'  => $req->applicantName,
    //         'advt_peroriod'   => $req->advtPeriod,
    //         'faher_name'      => $req->fatherName,
    //         'adress'=> $req->residenceAddress,
    //         'agency_name'      => $req->agencyName,
    //         'hoarding_type'   => $req->hoardingType,
    //         'allotment_date'  => $req->allotmentDate,
    //         'from_date'       => $req->from,
    //         'to_date'         => $req->to,
    //         'rate'            => $req->rate,
    //         'application_no' => $req->application_no,





    //     ];
    //     return $metaReqs;
    // }


}

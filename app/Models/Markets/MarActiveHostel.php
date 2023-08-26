<?php

namespace App\Models\Markets;

use App\MicroServices\DocumentUpload;
use App\Models\Advertisements\WfActiveDocument;
use App\Traits\WorkflowTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarActiveHostel extends Model
{
    use HasFactory;

    use WorkflowTrait;

    protected $guarded = [];
    protected $_applicationDate;

    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
    }

    /**
     * | Make metarequest for store function
     */
    public function metaReqs($req)
    {
        return [
            'applicant' => $req->applicantName,
            'license_year' => $req->licenseYear,
            'father' => $req->fatherName,
            'residential_address' => $req->residentialAddress,
            'residential_ward_id' => $req->residentialWardId,
            'permanent_address' => $req->permanentAddress,
            'permanent_ward_id' => $req->permanentWardId,
            'email' => $req->email,
            'mobile' => $req->mobile,
            'entity_name' => $req->entityName,
            'entity_address' => $req->entityAddress,
            'entity_ward_id' => $req->entityWardId,
            'hostel_type' => $req->hostelType,
            'holding_no' => $req->holdingNo,
            'trade_license_no' => $req->tradeLicenseNo,
            'longitude' => $req->longitude,
            'latitude' => $req->latitude,
            'organization_type' => $req->organizationType,
            'land_deed_type' => $req->landDeedType,
            'mess_type' => $req->messType,
            'no_of_beds' => $req->noOfBeds,
            'no_of_rooms' => $req->noOfRooms,

            'water_supply_type' => $req->waterSupplyType,
            'electricity_type' => $req->electricityType,
            'security_type' => $req->securityType,
            'cctv_camera' => $req->cctvCamera,
            'fire_extinguisher' => $req->fireExtinguisher,
            'entry_gate' => $req->entryGate,
            'exit_gate' => $req->exitGate,
            'two_wheelers_parking' => $req->twoWheelersParking,
            'four_wheelers_parking' => $req->fourWheelersParking,
            'aadhar_card' => $req->aadharCard,
            'pan_card' => $req->panCard,
            'rule' => $req->rule,
            // 'is_school_college_univ'=>$req->isSchoolCollegeUniv,
            // 'school_college_univ_name'=>$req->schoolCollegeUnivName,
            'is_approve_by_govt' => $req->isApproveByGovt,
            'application_no' => $req->application_no,
            // 'govt_type'=>$req->govtType,
        ];
    }

    // Store Application Foe Hostel(1)
    public function addNew($req)
    {
        $bearerToken = $req->token;
        // $workflowId = Config::get('workflow-constants.HOSTEL');                            // 350
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);
        $ulbWorkflows = $ulbWorkflows['data'];
        //  $mApplicationNo = ['application_no' => 'HOSTEL-' . random_int(100000, 999999)];                  // Generate Application No
        $ulbWorkflowReqs = [                                                                             // Workflow Meta Requests
            'workflow_id' => $ulbWorkflows['id'],
            'initiator_role_id' => $ulbWorkflows['initiator_role_id'],
            'current_role_id' => $ulbWorkflows['initiator_role_id'],
            'last_role_id' => $ulbWorkflows['initiator_role_id'],
            'finisher_role_id' => $ulbWorkflows['finisher_role_id'],
        ];
        $mDocuments = $req->documents;

        $metaReqs = array_merge(
            [
                'ulb_id' => $req->ulbId,
                'citizen_id' => $req->citizenId,
                'application_date' => $this->_applicationDate,
                'ip_address' => $$req->ipAddress,
                'application_type' => "New Apply"
            ],
            $this->metaReqs($req),
            $ulbWorkflowReqs
        );                                                                                          // Add Relative Path as Request and Client Ip Address etc.
        $tempId = MarActiveHostel::create($metaReqs)->id;
        $this->uploadDocument($tempId, $mDocuments, $req->auth);

        return $req->application_no;
    }


    // Renew Application For Hostel(1)
    public function renewApplication($req)
    {
        // $bearerToken = $req->bearerToken();
        $bearerToken = $req->token;
        // $workflowId = Config::get('workflow-constants.HOSTEL');                            // 350
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);
        $ulbWorkflows = $ulbWorkflows['data'];
        $mRenewNo = ['renew_no' => 'HOSTEL/REN-' . random_int(100000, 999999)];                  // Generate Application No
        $details = MarHostel::find($req->applicationId);                              // Find Previous Application No
        $mLicenseNo = ['license_no' => $details->license_no];
        $ulbWorkflowReqs = [                                                                             // Workflow Meta Requests
            'workflow_id' => $ulbWorkflows['id'],
            'initiator_role_id' => $ulbWorkflows['initiator_role_id'],
            'current_role_id' => $ulbWorkflows['initiator_role_id'],
            'last_role_id' => $ulbWorkflows['initiator_role_id'],
            'finisher_role_id' => $ulbWorkflows['finisher_role_id'],
        ];
        $mDocuments = $req->documents;

        $metaReqs = array_merge(
            [
                'ulb_id' => $req->ulbId,
                'citizen_id' => $req->citizenId,
                'application_date' => $this->_applicationDate,
                'ip_address' => $$req->ipAddress,
                'application_type' => "Renew"
            ],
            $this->metaReqs($req),
            $mLicenseNo,
            $mRenewNo,
            $ulbWorkflowReqs
        );                                                                                          // Add Relative Path as Request and Client Ip Address etc.
        $tempId = MarActiveHostel::create($metaReqs)->id;
        $this->uploadDocument($tempId, $mDocuments, $req->auth);

        return $mRenewNo['renew_no'];;
    }

    /**
     * upload Document By Citizen At the time of Registration
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocument($tempId, $documents, $auth)
    {
        $docUpload = new DocumentUpload;
        $mWfActiveDocument = new WfActiveDocument();
        $mMarActiveHostel = new MarActiveHostel();
        $relativePath = Config::get('constants.HOSTEL.RELATIVE_PATH');

        collect($documents)->map(function ($doc) use ($tempId, $auth, $docUpload, $mWfActiveDocument, $mMarActiveHostel, $relativePath) {
            $metaReqs = array();
            $getApplicationDtls = $mMarActiveHostel->getApplicationDtls($tempId);
            $refImageName = $doc['docCode'];
            $refImageName = $getApplicationDtls->id . '-' . $refImageName;
            $documentImg = $doc['image'];
            $imageName = $docUpload->upload($refImageName, $documentImg, $relativePath);
            $metaReqs['moduleId'] = Config::get('workflow-constants.MARKET_MODULE_ID');
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
            foreach($metaReqs as $key=>$val)
            {
                $mWfActiveDocument->$key = $val;
            }
            $mWfActiveDocument->save();
        });
    }

    /**
     * | Get application details by Id
     */
    public function getApplicationDtls($appId)
    {

        return MarActiveHostel::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * | Get Application Inbox List by Role Ids
     * | @param roleIds $roleIds
     */
    public function listInbox($roleIds, $ulbId)
    {
        $inbox = DB::table('mar_active_hostels')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address',
                'application_type',
            )
            ->orderByDesc('id')
            ->where('parked', NULL)
            ->where('ulb_id', $ulbId)
            ->whereIn('current_role_id', $roleIds);
        // ->get();
        return $inbox;
    }

    /**
     * | Get Application Outbox List by Role Ids
     */
    public function listOutbox($roleIds, $ulbId)
    {
        $outbox = DB::table('mar_active_hostels')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address',
                'application_type',
            )
            ->orderByDesc('id')
            ->where('parked', NULL)
            ->where('ulb_id', $ulbId)
            ->whereNotIn('current_role_id', $roleIds);
        // ->get();
        return $outbox;
    }

    /**
     * | Get Application Details by id
     * | @param SelfAdvertisements id
     */
    public function getDetailsById($id, $type = NULL)
    {
        $details = array();
        if ($type == 'Active' || $type == NULL) {
            $details = DB::table('mar_active_hostels')
                ->select(
                    'mar_active_hostels.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'ht.string_parameter as hosteltype',
                    'mt.string_parameter as messtype',
                    'ot.string_parameter as organizationtype',
                    'ldt.string_parameter as landDeedTypeName',
                    'st.string_parameter as securityType',
                    'et.string_parameter as electricityType',
                    'wst.string_parameter as waterSupplyType',
                )
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_active_hostels.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'mar_active_hostels.license_year')
                ->leftJoin('ref_adv_paramstrings as ht', 'ht.id', '=', 'mar_active_hostels.hostel_type')
                ->leftJoin('ref_adv_paramstrings as mt', 'mt.id', '=', 'mar_active_hostels.mess_type')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_active_hostels.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_active_hostels.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_active_hostels.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_active_hostels.organization_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_active_hostels.land_deed_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_active_hostels.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_active_hostels.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_active_hostels.water_supply_type')
                ->where('mar_active_hostels.id', $id)
                ->first();
        } elseif ($type == 'Reject') {
            $details = DB::table('mar_rejected_hostels')
                ->select(
                    'mar_rejected_hostels.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'ht.string_parameter as hosteltype',
                    'mt.string_parameter as messtype',
                    'ot.string_parameter as organizationtype',
                    'ldt.string_parameter as landDeedTypeName',
                    'st.string_parameter as securityType',
                    'et.string_parameter as electricityType',
                    'wst.string_parameter as waterSupplyType',
                )
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_rejected_hostels.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'mar_rejected_hostels.license_year')
                ->leftJoin('ref_adv_paramstrings as ht', 'ht.id', '=', 'mar_rejected_hostels.hostel_type')
                ->leftJoin('ref_adv_paramstrings as mt', 'mt.id', '=', 'mar_rejected_hostels.mess_type')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_rejected_hostels.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_rejected_hostels.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_rejected_hostels.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_rejected_hostels.organization_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_rejected_hostels.land_deed_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_rejected_hostels.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_rejected_hostels.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_rejected_hostels.water_supply_type')
                ->where('mar_rejected_hostels.id', $id)
                ->first();
        } elseif ($type == 'Approve') {
            $details = DB::table('mar_hostels')
                ->select(
                    'mar_hostels.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'ht.string_parameter as hosteltype',
                    'mt.string_parameter as messtype',
                    'ot.string_parameter as organizationtype',
                    'ldt.string_parameter as landDeedTypeName',
                    'st.string_parameter as securityType',
                    'et.string_parameter as electricityType',
                    'wst.string_parameter as waterSupplyType',
                )
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_hostels.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'mar_hostels.license_year')
                ->leftJoin('ref_adv_paramstrings as ht', 'ht.id', '=', 'mar_hostels.hostel_type')
                ->leftJoin('ref_adv_paramstrings as mt', 'mt.id', '=', 'mar_hostels.mess_type')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_hostels.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_hostels.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_hostels.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_hostels.organization_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_hostels.land_deed_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_hostels.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_hostels.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_hostels.water_supply_type')
                ->where('mar_hostels.id', $id)
                ->first();
        }
        return json_decode(json_encode($details), true);            // Convert Std Class to Array
    }

    /**
     * | Get Citizen Applied applications
     * | @param citizenId
     */
    public function listAppliedApplications($citizenId)
    {
        return MarActiveHostel::where('mar_active_hostels.citizen_id', $citizenId)
            ->select(
                'mar_active_hostels.id',
                'mar_active_hostels.application_no',
                DB::raw("TO_CHAR(mar_active_hostels.application_date, 'DD-MM-YYYY') as application_date"),
                'mar_active_hostels.applicant',
                'mar_active_hostels.entity_name',
                'mar_active_hostels.entity_address',
                'mar_active_hostels.doc_upload_status',
                'mar_active_hostels.doc_verify_status',
                'mar_active_hostels.application_type',
                'mar_active_hostels.parked',
                'um.ulb_name as ulb_name',
                'wr.role_name',
            )
            ->join('wf_roles as wr', 'wr.id', '=', 'mar_active_hostels.current_role_id')
            ->join('ulb_masters as um', 'um.id', '=', 'mar_active_hostels.ulb_id')
            ->orderByDesc('mar_active_hostels.id')
            ->get();
    }

    /**
     * | Get a particular application details by application Id
     */
    public function getHostelDetails($appId)
    {
        return MarActiveHostel::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * | Get All application ULB wise
     */
    public function getHostelList($ulbId)
    {
        return MarActiveHostel::select('*')
            ->where('mar_active_hostels.ulb_id', $ulbId);
    }

    /**
     * | Reupload Documents
     */
    public function reuploadDocument($req)
    {
        $docUpload = new DocumentUpload;
        $docDetails = WfActiveDocument::find($req->id);
        $relativePath = Config::get('constants.HOSTEL.RELATIVE_PATH');

        $refImageName = $docDetails['doc_code'];
        $refImageName = $docDetails['active_id'] . '-' . $refImageName;
        $documentImg = $req->image;
        $imageName = $docUpload->upload($refImageName, $documentImg, $relativePath);

        $metaReqs['moduleId'] = Config::get('workflow-constants.MARKET_MODULE_ID');
        $metaReqs['activeId'] = $docDetails['active_id'];
        $metaReqs['workflowId'] = $docDetails['workflow_id'];
        $metaReqs['ulbId'] = $docDetails['ulb_id'];
        $metaReqs['relativePath'] = $relativePath;
        $metaReqs['document'] = $imageName;
        $metaReqs['docCode'] = $docDetails['doc_code'];
        $metaReqs['ownerDtlId'] = $docDetails['ownerDtlId'];
        $a = new Request($metaReqs);
        $mWfActiveDocument = new WfActiveDocument();
        $mWfActiveDocument->postDocuments($a, $req->auth);
        $docDetails->current_status = '0';
        $docDetails->save();
        return $docDetails['active_id'];
    }

    /**
     * | Get Application Details For Update 
     */
    public function getApplicationDetailsForEdit($appId)
    {
        return MarActiveHostel::select(
            'mar_active_hostels.*',
            'mar_active_hostels.hostel_type as hostel_type_id',
            'mar_active_hostels.organization_type as organization_type_id',
            'mar_active_hostels.land_deed_type as land_deed_type_id',
            'mar_active_hostels.mess_type as mess_type_id',
            'mar_active_hostels.water_supply_type as water_supply_type_id',
            'mar_active_hostels.electricity_type as electricity_type_id',
            'mar_active_hostels.security_type as security_type_id',
            'mar_active_hostels.no_of_rooms as noOfRooms',
            'mar_active_hostels.no_of_beds as noOfBeds',
            'ly.string_parameter as license_year_name',
            DB::raw("case when mar_active_hostels.is_approve_by_govt = true then 'Yes'
                        else 'No' end as is_approve_by_govt_name"),
            DB::raw("case when mar_active_hostels.is_approve_by_govt = true then 1
                        else 0 end as is_approve_by_govt_id"),
            'lt.string_parameter as hostel_type_name',
            'ot.string_parameter as organization_type_name',
            'ldt.string_parameter as land_deed_type_name',
            'mt.string_parameter as mess_type_name',
            'wt.string_parameter as water_supply_type_name',
            'et.string_parameter as electricity_type_name',
            'st.string_parameter as security_type_name',
            'pw.ward_name as permanent_ward_name',
            'ew.ward_name as entity_ward_name',
            'rw.ward_name as residential_ward_name',
            'ulb.ulb_name',
            DB::raw("'Hostel' as headerTitle")
        )
            ->leftJoin('ref_adv_paramstrings as ly', 'ly.id', '=', DB::raw('mar_active_hostels.license_year::int'))
            ->leftJoin('ulb_ward_masters as rw', 'rw.id', '=', DB::raw('mar_active_hostels.residential_ward_id::int'))
            ->leftJoin('ref_adv_paramstrings as lt', 'lt.id', '=', DB::raw('mar_active_hostels.hostel_type::int'))
            ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', DB::raw('mar_active_hostels.organization_type::int'))
            ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', DB::raw('mar_active_hostels.land_deed_type::int'))
            ->leftJoin('ref_adv_paramstrings as mt', 'mt.id', '=', DB::raw('mar_active_hostels.mess_type::int'))
            ->leftJoin('ref_adv_paramstrings as wt', 'wt.id', '=', DB::raw('mar_active_hostels.water_supply_type::int'))
            ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', DB::raw('mar_active_hostels.electricity_type::int'))
            ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', DB::raw('mar_active_hostels.security_type::int'))
            ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_active_hostels.entity_ward_id')
            ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_active_hostels.permanent_ward_id')
            ->leftJoin('ulb_masters as ulb', 'ulb.id', '=', 'mar_active_hostels.ulb_id')
            ->where('mar_active_hostels.id', $appId)->first();
    }

    /**
     * | Update or edit application 
     */
    public function updateApplication($req)
    {
        $MarActiveHostel = MarActiveHostel::findorfail($req->applicationId);
        $MarActiveHostel->remarks = $req->remarks;
        $MarActiveHostel->organization_type = $req->organizationType;
        $MarActiveHostel->land_deed_type = $req->landDeedType;
        $MarActiveHostel->water_supply_type = $req->waterSupplyType;
        $MarActiveHostel->electricity_type = $req->electricityType;
        $MarActiveHostel->security_type = $req->securityType;
        $MarActiveHostel->cctv_camera = $req->cctvCamera;
        $MarActiveHostel->fire_extinguisher = $req->fireExtinguisher;
        $MarActiveHostel->entry_gate = $req->entryGate;
        $MarActiveHostel->exit_gate = $req->exitGate;
        $MarActiveHostel->two_wheelers_parking = $req->twoWheelersParking;
        $MarActiveHostel->four_wheelers_parking = $req->fourWheelersParking;
        $MarActiveHostel->no_of_beds = $req->noOfBeds;
        $MarActiveHostel->no_of_rooms = $req->noOfRooms;
        $MarActiveHostel->save();
        // dd($mMarActiveBanquteHall);
        return $MarActiveHostel;
    }

    /**
     * | Get Pending applications
     * | @param citizenId
     */
    public function allPendingList()
    {
        return MarActiveHostel::all();
    }

    /**
     * | Pending List For Report
     */
    public function pendingListForReport()
    {
        return MarActiveHostel::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'rule', 'organization_type', 'hostel_type', 'ulb_id', 'license_year', DB::raw("'Active' as application_status"));
    }
}

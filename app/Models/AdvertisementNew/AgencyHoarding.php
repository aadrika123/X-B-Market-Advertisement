<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use App\MicroServices\DocumentUpload;
use App\Models\Advertisements\WfActiveDocument;
use Illuminate\Support\Facades\DB;
use App\Traits\WorkflowTrait;
use Illuminate\Http\Request;

class AgencyHoarding extends Model
{
    use HasFactory;

    use WorkflowTrait;
    protected $guarded = [];
    protected $_applicationDate;

    // Initializing construction
    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
    }
    # add data 
    public function creteData($metaReqs)
    {
        self::create($metaReqs);
    }
    /**
     * GET ALL AGENCY
     */
    public function getAllDtls()
    {
        return self::where('status', '1')
            ->orderByDesc('id')
            ->get();
    }
    /**
     * 
     */
    public function checkdtlsById($agencyId)
    {
        return self::where('id', $agencyId)
            ->where('status', 1)
            ->first();
    }

    public function updatedtl($metaRequest, $agencyId)
    {
        self::where('id', $agencyId)
            ->update($metaRequest);
    }
    # update status 
    public function updateStatus($agencyId)
    {
        return self::where('id', $agencyId)
            ->update([
                'status' => 0
            ]);
    }
    public function saveRequestDetails($request, $refRequest, $applicationNo, $ulbId)
    {
        $mAgencyHoarding = new AgencyHoarding();
        $mAgencyHoarding->agency_id                      = $request->agencyId;
        $mAgencyHoarding->hoarding_id                    = $request->hoardingId;
        $mAgencyHoarding->agency_name                    = $request->agencyName;
        // $mAgencyHoarding->hoarding_type                  = $request->hoardingType;
        $mAgencyHoarding->allotment_date                 = $request->allotmentDate ?? null;
        $mAgencyHoarding->rate                           = $request->rate;
        $mAgencyHoarding->from_date                      = $request->from;
        $mAgencyHoarding->to_date                        = $request->to;
        $mAgencyHoarding->user_id                        = $refRequest['empId'];
        $mAgencyHoarding->user_type                      = $refRequest['userType'];
        $mAgencyHoarding->apply_from                     = $refRequest['applyFrom'];
        $mAgencyHoarding->initiator                      = $refRequest['initiatorRoleId'];
        $mAgencyHoarding->workflow_id                    = $refRequest['ulbWorkflowId'];
        $mAgencyHoarding->ulb_id                         = $ulbId;
        $mAgencyHoarding->finisher                       = $refRequest['finisherRoleId'];
        $mAgencyHoarding->current_role_id                = $refRequest['initiatorRoleId'];
        $mAgencyHoarding->application_no                 = $applicationNo;
        $mAgencyHoarding->address                        = $request->residenceAddress;
        // $mAgencyHoarding->doc_status                     = $request->doc_status ?? null;
        $mAgencyHoarding->doc_upload_status              = $request->doc_upload_status ?? null;
        $mAgencyHoarding->advertiser                     = $request->advertiser;
        $mAgencyHoarding->apply_date                     = $this->_applicationDate;
        $mAgencyHoarding->adv_type                       = $request->hoardingType;
        $mAgencyHoarding->hoard_size_id                  = $request->squareFeetId;
        $mAgencyHoarding->application_type               = $request->applicationType;
        $mAgencyHoarding->size_square_feet               = $request->squarefeet;
        $mAgencyHoarding->total_ballon                   = $request->Noofballons;
        $mAgencyHoarding->total_vehicle                  = $request->Noofvehicle;
        $mAgencyHoarding->vehicle_type_id                = $request->vehicleType;
        $mAgencyHoarding->purpose                        = $request->purpose;
        $mAgencyHoarding->no_of_hoarding                 = $request->Noofhoardings;
        $mAgencyHoarding->mobile_no                      = $request->mobileNo;
        $mAgencyHoarding->location                      = $request->location;
        if ($request->applicationType == 'PERMANANT') {
            $mAgencyHoarding->property_type_id                  = $request->propertyId;
        }
        $mAgencyHoarding->save();
        return $mAgencyHoarding->id;
    }
    /**
     * | Get Application Inbox List by Role Ids
     * | @param roleIds $roleIds
     */
    public function listInbox($roleIds, $ulbId)
    {
        $inbox = DB::table('agency_hoardings')
            ->select(
                'agency_hopardings.*'
            )
            ->orderByDesc('id')
            ->where('parked', NULL)
            ->where('ulb_id', $ulbId)
            ->whereIn('current_role_id', $roleIds);
        // ->get();
        return $inbox;
    }
    public function getApplicationId($applicationId)
    {
        return self::select(
            'agency_hoardings.*'
        )
            ->where('id', $applicationId)
            ->where('status', 1);
    }
    /**
     * | Deactivate the doc Upload Status 
     */
    public function updateUploadStatus($applicationId, $status)
    {
        return  AgencyHoarding::where('id', $applicationId)
            ->where('status', true)
            ->update([
                "doc_upload_status" => $status
            ]);
    }
    /** 
     * | Update the Doc related status in active table 
     */
    public function updateDocStatus($applicationId, $status)
    {
        AgencyHoarding::where('id', $applicationId)
            ->update([
                // 'doc_upload_status' => true,
                'doc_verify_status' => $status
            ]);
    }
    /**
     * get details by id for workflow view 
     */
    public function getFullDetails($request)
    {
        return self::select(
            'agency_hoardings.*',
            'ulb_masters.ulb_name',
            'wf_roles.role_name AS current_role_name',
            'hoarding_masters.ward_id',
            'ulb_ward_masters.ward_name',
            'm_circle.circle_name as zone_name',
            'agency_masters.agency_name as agencyName',
            "hoarding_types.type as hoarding_type",

        )

            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->join('hoarding_masters', 'hoarding_masters.id', 'agency_hoardings.hoarding_id')
            ->join('wf_roles', 'wf_roles.id', '=', 'agency_hoardings.current_role_id')
            ->join('hoarding_types', 'hoarding_types.id', 'hoarding_masters.hoarding_type_id')
            ->leftjoin('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'hoarding_masters.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoardings.ulb_id')
            ->where('agency_hoardings.id', $request->applicationId)
            ->where('agency_hoardings.status', true);
    }
    /**
     * | Get application details by Id
     */
    public function getApplicationDtls($appId)
    {
        return self::select('*')
            ->where('id', $appId)
            ->first();
    }
    /**
     search application by application number 
     */
    public function getByItsDetailsV2($req, $key, $refNo, $email)
    {
        return self::select(
            "agency_hoardings.id",
            "agency_hoardings.rate",
            "agency_hoardings.from_date",
            "agency_hoardings.to_date",
            "agency_hoardings.current_role_id",
            "hoarding_types.type as hoarding_type",
            "hoarding_masters.address",
            "hoarding_masters.hoarding_no",
            "agency_hoardings.approve",
            "agency_hoardings.application_no",
            "agency_hoardings.apply_date",
            "agency_hoardings.registration_no",
            "m_circle.circle_name as zone_name",
            "ulb_ward_masters.ward_name",
            "agency_masters.agency_name",
            DB::raw("CASE 
            WHEN approve = 0 THEN 'Pending'
            WHEN approve = 1 THEN 'Approved'
            WHEN approve = 2 THEN 'Rejected'
            ELSE 'Unknown Status'
          END AS approval_status"),
            DB::raw("CASE 
            WHEN agency_hoardings.current_role_id = 6 THEN 'AT LIPIK'
            WHEN agency_hoardings.current_role_id = 10 THEN 'AT TAX  SUPRERINTENDENT'
            ELSE 'Unknown Role'
        END AS application_at")
        )
            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->join('hoarding_masters', 'hoarding_masters.id', 'agency_hoardings.hoarding_id')
            ->join('hoarding_types', 'hoarding_types.id', 'hoarding_masters.hoarding_type_id')
            ->join('wf_roles', 'wf_roles.id', '=', 'agency_hoardings.current_role_id')
            ->leftjoin('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'hoarding_masters.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoardings.ulb_id')
            ->where('agency_hoardings.status', 1)
            ->where('agency_masters.email', $email)
            ->where('agency_masters.status', 1)
            ->orderBy('agency_hoardings.id', 'desc');
    }
    /**
     * get details of approve applications 
     */
    public function getApproveDetails($request)
    {
        return self::select(
            'agency_hoardings.from_date',
            'agency_hoardings.to_date',
            'agency_hoardings.advertiser',
            'ulb_masters.ulb_name',
            'wf_roles.role_name AS current_role_name',
            'hoarding_masters.ward_id',
            'hoarding_masters.address',
            'ulb_ward_masters.ward_name',
            'm_circle.circle_name as zone_name',
            'agency_masters.agency_name as agencyName',
            'agency_hoardings.registration_no',
            'agency_hoardings.allotment_date',
            'agency_hoardings.purpose',
            'agency_hoardings.adv_type',
            'agency_hoardings.application_type',
            'agency_hoardings.total_vehicle',
            'measurement_sizes.measurement',
            'agency_hoardings.total_ballon',
            'hoarding_rates.size',
            'agency_hoardings.size_square_feet',
            'agency_hoardings.application_no',
            'agency_hoardings.no_of_hoarding'

        )
            ->leftjoin('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->leftjoin('hoarding_masters', 'hoarding_masters.id', 'agency_hoardings.hoarding_id')
            ->leftjoin('wf_roles', 'wf_roles.id', '=', 'agency_hoardings.current_role_id')
            ->leftJoin('measurement_sizes', function ($join) {
                $join->on('measurement_sizes.id', '=', 'agency_hoardings.hoard_size_id')
                    ->where('measurement_sizes.status', 1);
            })
            ->leftJoin('hoarding_rates', function ($join) {
                $join->on('hoarding_rates.id', '=', 'agency_hoardings.hoard_size_id')
                    ->where('hoarding_rates.status', 1);
            })


            ->Join('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
            ->Join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'hoarding_masters.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoardings.ulb_id')
            ->where('agency_hoardings.id', $request->applicationId)
            ->where('agency_hoardings.status', true)
            ->where('agency_hoardings.approve', 1)
            ->first();
    }
    /**
     * get details of approve applications 
     */
    public function getAppicationDetails($applicationId)
    {
        return self::select(
            'agency_hoardings.id',
            'agency_hoardings.from_date',
            'agency_hoardings.to_date',
            'agency_hoardings.advertiser',
            'ulb_masters.ulb_name',
            'wf_roles.role_name AS current_role_name',
            'hoarding_masters.ward_id',
            'hoarding_masters.address',
            'ulb_ward_masters.ward_name',
            'm_circle.circle_name as zone_name',
            'agency_masters.agency_name as agencyName',
            'agency_hoardings.registration_no',
            'agency_hoardings.allotment_date',
            'agency_hoardings.purpose',
            'agency_hoardings.adv_type',
            'agency_hoardings.application_type',
            'agency_hoardings.total_vehicle',
            'measurement_sizes.measurement',
            'agency_hoardings.total_ballon',
            'hoarding_rates.size',
            'agency_hoardings.size_square_feet',
            'agency_hoardings.application_no',
            'agency_hoardings.no_of_hoarding',
            'agency_hoardings.ulb_id',
            'agency_hoardings.initiator'
        )
            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->join('hoarding_masters', 'hoarding_masters.id', 'agency_hoardings.hoarding_id')
            ->join('wf_roles', 'wf_roles.id', '=', 'agency_hoardings.current_role_id')
            ->leftJoin('measurement_sizes', function ($join) {
                $join->on('measurement_sizes.id', '=', 'agency_hoardings.hoard_size_id')
                    ->where('measurement_sizes.status', 1);
            })
            ->leftJoin('hoarding_rates', function ($join) {
                $join->on('hoarding_rates.id', '=', 'agency_hoardings.hoard_size_id')
                    ->where('hoarding_rates.status', 1);
            })


            ->Join('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
            ->Join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'hoarding_masters.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoardings.ulb_id')
            ->where('agency_hoardings.id', $applicationId)
            ->where('agency_hoardings.status', true);
        // ->where('agency_hoardings.approve', 1)
    }

    /**
     * application details of hoarding
     */
    public function getApplicationDtl($email)
    {
        return self::select(
            'agency_hoardings.id',
            'agency_hoardings.application_no',
            'agency_hoardings.rate',
            'agency_hoardings.from_date',
            'agency_hoardings.to_date',
            'agency_hoardings.apply_date',
            'agency_hoardings.advertiser',
            'agency_masters.mobile',
            'ulb_masters.ulb_name',
            'ulb_ward_masters.ward_name',
            'm_circle.circle_name as zone_name',
            'agency_masters.agency_name as agencyName',
            'hoarding_masters.hoarding_no',
            "hoarding_types.type as hoarding_type",
        )
            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->leftjoin('hoarding_masters', 'hoarding_masters.id', 'agency_hoardings.hoarding_id')
            ->join('wf_roles', 'wf_roles.id', '=', 'agency_hoardings.current_role_id')
            ->join('hoarding_types', 'hoarding_types.id', 'hoarding_masters.hoarding_type_id')
            ->join('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'hoarding_masters.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoardings.ulb_id')
            ->where('agency_masters.email', $email)
            ->where('agency_hoardings.status', true)
            ->where('agency_masters.status', 1)
            ->orderBy('agency_hoardings.id', 'desc')
            ->get();
    }
    #get temporary details 
    public function getApplicationDetails($email)
    {
        return self::select(
            'agency_hoardings.id',
            'agency_hoardings.application_no',
            'agency_hoardings.rate',
            'agency_hoardings.from_date',
            'agency_hoardings.to_date',
            'agency_hoardings.apply_date',
            'agency_hoardings.advertiser',
            'agency_masters.mobile',
            'agency_masters.agency_name as agencyName',

        )
            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->join('wf_roles', 'wf_roles.id', '=', 'agency_hoardings.current_role_id')
            ->where('agency_hoardings.hoarding_type', 2)
            ->where('agency_masters.email', $email)
            ->where('agency_hoardings.status', true)
            ->where('agency_masters.status', 1)
            ->orderBy('agency_hoardings.id', 'desc')
            ->get();
    }
    /**
     * get details by id for workflow view 
     */
    public function checkHoarding($hoardId)
    {
        return self::select(
            'agency_hoardings.*',

        )
            ->where('agency_hoardings.hoarding_id', $hoardId)
            ->where('agency_hoardings.status', true)
            ->get();
    }
    /**
     * | Reupload Documents
     */
    public function reuploadDocument($req)
    {
        $docUpload = new DocumentUpload;
        $docDetails = WfActiveDocument::find($req->docId);
        $relativePath   = Config::get('constants.AGENCY_ADVET.RELATIVE_PATH');

        $refImageName = $docDetails['doc_code'];
        $refImageName = $docDetails['active_id'] . '-' . $refImageName;
        $documentImg = $req->image;
        $imageName = $docUpload->upload($refImageName, $documentImg, $relativePath);

        $metaReqs['moduleId'] = Config::get('workflow-constants.ADVERTISMENT_MODULE');
        $metaReqs['activeId'] = $docDetails['active_id'];
        $metaReqs['workflowId'] = $docDetails['workflow_id'];
        $metaReqs['ulbId'] = $docDetails['ulb_id'];
        $metaReqs['relativePath'] = $relativePath;
        $metaReqs['document'] = $imageName;
        $metaReqs['docCode'] = $docDetails['doc_code'];
        $metaReqs['ownerDtlId'] = $docDetails['ownerDtlId'];
        $a = new Request($metaReqs);
        $mWfActiveDocument = new WfActiveDocument();
        $mWfActiveDocument->postDocuments($a, $req->auth, $req);
        $docDetails->current_status = '1';
        $docDetails->verify_status = '0';
        $docDetails->save();
        return $docDetails['active_id'];
    }
    /**
     * application details of hoarding
     */
    public function getRejectDocs($email, $workflowIds)
    {
        return self::select(
            'agency_hoardings.id',
            'wf_active_documents.id as docId',
            'agency_hoardings.application_no',
            'wf_active_documents.doc_code',
            'agency_hoardings.rate',
            'agency_hoardings.from_date',
            'agency_hoardings.to_date',
            'hoarding_masters.hoarding_no',
            "hoarding_types.type as hoarding_type",
            "workflow_tracks.message as reason",
            "workflow_tracks.workflow_id"

        )
            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->leftjoin('hoarding_masters', 'hoarding_masters.id', 'agency_hoardings.hoarding_id')
            ->join('hoarding_types', 'hoarding_types.id', 'hoarding_masters.hoarding_type_id')
            ->join('wf_active_documents', 'wf_active_documents.active_id', 'agency_hoardings.id')
            // ->join('workflow_tracks', 'workflow_tracks.ref_table_id_value', 'agency_hoardings.id')
            ->where('agency_masters.email', $email)
            ->where('agency_masters.status', 1)
            ->where('wf_active_documents.verify_status', 2)
            ->where('wf_active_documents.status', '!=', 0)
            ->where('wf_active_documents.workflow_id', $workflowIds)
            ->leftJoin('workflow_tracks', function ($join) use ($workflowIds) {
                $join->on('workflow_tracks.ref_table_id_value', 'agency_hoardings.id')
                    ->where('workflow_tracks.status', true)
                    ->where('workflow_tracks.message', '<>', null)
                    ->where('workflow_tracks.workflow_id', $workflowIds);
            })
            ->distinct('agency_hoardings.id')
            ->where('agency_hoardings.status', true)
            ->where('agency_masters.status', 1)
            ->orderBy('agency_hoardings.id', 'desc');
    }
    #details by ID 
    public function getRejectDocbyId($email, $workflowIds, $applicationId)
    {
        return self::select(
            'agency_hoardings.id',
            'wf_active_documents.id as docId',
            'wf_active_documents.doc_code',
            'wf_active_documents.verify_status',
            "wf_active_documents.workflow_id"
        )
            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->leftjoin('hoarding_masters', 'hoarding_masters.id', 'agency_hoardings.hoarding_id')
            ->join('hoarding_types', 'hoarding_types.id', 'hoarding_masters.hoarding_type_id')
            ->leftjoin('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'hoarding_masters.ward_id')
            ->join('wf_active_documents', 'wf_active_documents.active_id', 'agency_hoardings.id')
            ->where('agency_hoardings.id', $applicationId)
            ->where('agency_masters.email', $email)
            ->where('agency_masters.status', 1)
            ->where('wf_active_documents.status', 1)
            ->where('wf_active_documents.verify_status', '!=', 0)
            ->where('wf_active_documents.workflow_id', $workflowIds)
            // ->where('agency_hoardings.status', true)
            ->where('agency_masters.status', 1)
            ->get();
    }

    /**
     * | Get all details according to key 
     */
    public function getAllApprovdApplicationDetails($email)
    {
        return DB::table('agency_hoarding_approve_applications')
            ->leftJoin('wf_roles', 'wf_roles.id', 'agency_hoarding_approve_applications.current_role_id')
            ->join('agency_hoardings', 'agency_hoardings.id', 'agency_hoarding_approve_applications.id')
            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->where('agency_masters.email', $email)
            ->where('agency_masters.status', 1)
            // ->leftJoin('rig_trans', function ($join) {
            //     $join->on('rig_trans.related_id', '=', 'agency_hoardings.id')
            //         ->where('rig_trans.status', 1);
            // });
        ;
    }

    /**
     * | Save the status in Active table
     */
    public function saveApplicationStatus($applicationId, $refRequest)
    {
        return self::where('id', $applicationId)
            ->update($refRequest);
    }

    /**
     * | Get application details by id
     */
    public function getApplicationById($id)
    {
        return self::join('ulb_masters', 'ulb_masters.id', 'agency_hoardings.ulb_id')
            ->where('agency_hoardings.id', $id)
            ->where('agency_hoardings.status', 1);
    }
}

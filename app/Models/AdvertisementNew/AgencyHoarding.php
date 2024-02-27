<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use App\MicroServices\DocumentUpload;
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
        $mAgencyHoarding->hoarding_type                  = $request->hoardingType;
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
        $mAgencyHoarding->current_role_id                   = $refRequest['initiatorRoleId'];
        $mAgencyHoarding->application_no                 = $applicationNo;
        $mAgencyHoarding->address                        = $request->residenceAddress;
        $mAgencyHoarding->doc_status                     = $request->doc_status ?? null;
        $mAgencyHoarding->doc_upload_status              = $request->doc_upload_status ?? null;
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
            'agency_masters.agency_name as agencyName'

        )

            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->join('hoarding_masters', 'hoarding_masters.id', 'agency_hoardings.hoarding_id')
            ->join('wf_roles', 'wf_roles.id', '=', 'agency_hoardings.current_role_id')
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
    public function getByItsDetailsV2($req, $key, $refNo)
    {
        return self::select(
            "agency_hoardings.id as agencyHoardId",
            "agency_hoardings.rate",
            "agency_hoardings.from_date",
            "agency_hoardings.to_date",
            "agency_hoardings.hoarding_type",
            "agency_hoardings.address",
            "agency_hoardings.application_no",
            "agency_hoardings.apply_date",
            "agency_hoardings.registration_no",
            "m_circle.circle_name as zone_name",
            "ulb_ward_masters.ward_name",
            "agency_masters.agency_name"
        )
            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->join('hoarding_masters', 'hoarding_masters.id', 'agency_hoardings.hoarding_id')
            ->join('wf_roles', 'wf_roles.id', '=', 'agency_hoardings.current_role_id')
            ->leftjoin('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'hoarding_masters.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoardings.ulb_id')
            ->where('agency_hoardings.status', 1)
            ->where('agency_hoardings.' . $key, 'LIKE', '%' . $refNo . '%')
            ->where('agency_hoardings.approve', 1);
    }
     /**
     * get details of approve applications 
     */
    public function getApproveDetails($request)
    {
        return self::select(
            'agency_hoardings.*',
            'ulb_masters.ulb_name',
            'wf_roles.role_name AS current_role_name',
            'hoarding_masters.ward_id',
            'ulb_ward_masters.ward_name',
            'm_circle.circle_name as zone_name',
            'agency_masters.agency_name as agencyName'

        )

            ->join('agency_masters', 'agency_masters.id', 'agency_hoardings.agency_id')
            ->join('hoarding_masters', 'hoarding_masters.id', 'agency_hoardings.hoarding_id')
            ->join('wf_roles', 'wf_roles.id', '=', 'agency_hoardings.current_role_id')
            ->leftjoin('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'hoarding_masters.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoardings.ulb_id')
            ->where('agency_hoardings.id', $request->applicationId)
            ->where('agency_hoardings.status', true)
            ->where('agency_hoardings.approve',1);
    }
}

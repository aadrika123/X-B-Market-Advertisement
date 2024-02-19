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
    public function saveRequestDetails($request, $refRequest, $applicationNo)
    {
        $mAgencyHoarding = new AgencyHoarding();
        $mAgencyHoarding->agency_id                      = $request->id;
        $mAgencyHoarding->agency_name                    = $request->agencyName;
        $mAgencyHoarding->hoarding_type                  = $request->hoardingType;
        // $mAgencyHoarding->hoarding_id                    = $request->hoardingType;
        $mAgencyHoarding->allotment_date                 = $request->allotmentDate?? null;
        $mAgencyHoarding->rate                           = $request->rate;
        $mAgencyHoarding->from_date                      = $request->from;
        $mAgencyHoarding->to_date                        = $request->to;
        // $mAgencyHoarding->user_id                        = $refRequest[''];
        $mAgencyHoarding->user_type                      = $refRequest['userType'];
        $mAgencyHoarding->apply_from                     = $refRequest['applyFrom'];
        $mAgencyHoarding->initiator                      = $refRequest['initiatorRoleId'];
        $mAgencyHoarding->workflow_id                    = $refRequest['ulbWorkflowId'];
        $mAgencyHoarding->ulb_id                         = $request->ulbId;
        $mAgencyHoarding->finisher                       = $refRequest['finisherRoleId'];
        $mAgencyHoarding->current_role                   = $refRequest['initiatorRoleId'];
        $mAgencyHoarding->application_no                 = $applicationNo;
        $mAgencyHoarding->address                        = $request->residenceAddress;
        $mAgencyHoarding->doc_status                     = $request->doc_status ?? null;
        $mAgencyHoarding->doc_upload_status              = $request->doc_upload_status ?? null;
        $mAgencyHoarding->save();
        return [
            "id" => $mAgencyHoarding->id
        ];
    }
}

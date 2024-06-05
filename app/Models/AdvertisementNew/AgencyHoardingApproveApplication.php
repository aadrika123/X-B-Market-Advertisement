<?php

namespace App\Models\AdvertisementNew;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AgencyHoardingApproveApplication extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $_applicationDate;
    // Initializing construction
    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
    }

    /**
     * | Get approved appliaction using the id 
     */
    public function getApproveApplication($applicationId)
    {
        return AgencyHoardingApproveApplication::where('id', $applicationId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * | Get Approved Application by applicationId
     */
    public function getRigApprovedApplicationById($registrationId)
    {
        return AgencyHoardingApproveApplication::select(
            DB::raw("REPLACE(agency_hoarding_approve_applications.application_type, '_', ' ') AS ref_application_type"),
            'agency_hoarding_approve_applications.id as approve_id',
            'agency_hoardings.id',
            "agency_hoarding_approve_applications.application_no",
            "agency_hoarding_approve_applications.apply_date",
            "agency_hoarding_approve_applications.address",
            "agency_hoarding_approve_applications.application_type",
            // "agency_haordings.payment_status",
            "agency_hoardings.from_date",
            "agency_hoardings.to_date",
            "agency_hoarding_approve_applications.status",
            // "agency_hoarding_approve_applications.registration_id",
            "agency_hoarding_approve_applications.parked",
            "agency_hoarding_approve_applications.doc_upload_status",
            // "agency_hoarding_approve_applications.registration_id",
            "agency_hoarding_approve_applications.doc_verify_status",
            // "agency_hoarding_approve_applications.approve_date",
            // "agency_hoarding_approve_applications.approve_end_date",
            "agency_hoarding_approve_applications.doc_verify_status",
            'agency_hoarding_approve_applications.status as registrationStatus',
            'agency_hoarding_approve_applications.ulb_id',
            // 'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            'agency_hoardings.no_of_hoarding as totalHoarding',
            'agency_hoardings.purpose',
            'agency_hoardings.advertiser',
            'agency_hoardings.mobile_no as mobileNo',
            'agency_hoardings.payment_status',
            DB::raw("CASE 
            WHEN agency_hoardings.payment_status = '1' THEN 'Paid'
            WHEN agency_hoardings.payment_status = '0' THEN 'Unpaid'
            END AS paymentStatus"),
            'agency_hoardings.rate as amount',
            'agency_hoardings.location',
        )
            ->join('ulb_masters', 'ulb_masters.id', 'agency_hoarding_approve_applications.ulb_id')
            // ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', 'agency_hoarding_approve_applications.ward_id')
            ->join('agency_hoardings', 'agency_hoardings.id', 'agency_hoarding_approve_applications.id')
            ->where('agency_hoarding_approve_applications.id', $registrationId);
    }

    /**
     * | Get application details according to id
     */
    public function getApproveDetailById($id)
    {
        return AgencyHoardingApproveApplication::join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoarding_approve_applications.ulb_id')
            ->where('agency_hoarding_approve_applications.application_id', $id)
            ->where('agency_hoarding_approve_applications.status', '<>', 0);
    }


    public function getApprovePaidApplication()
    {
        return AgencyHoardingApproveApplication::select('agency_hoarding_approve_applications.application_no','agency_hoardings.payment_status','agency_hoardings.from_date','agency_hoardings.to_date','agency_hoarding_approve_applications.adv_type','agency_hoarding_approve_applications.mobile_no')
            ->join('agency_hoardings', 'agency_hoardings.id', '=', 'agency_hoarding_approve_applications.id')
            ->where('agency_hoardings.payment_status', 1)
            ->where('agency_hoarding_approve_applications.status', true)
            ->orderByDesc('agency_hoarding_approve_applications.id');
           // ->get();
    }
}

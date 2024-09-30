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
            'agency_hoarding_approve_applications.id',
            "agency_hoarding_approve_applications.application_no",
            "agency_hoarding_approve_applications.apply_date",
            "agency_hoarding_approve_applications.address",
            "agency_hoarding_approve_applications.application_type",
            // "agency_haordings.payment_status",
            "agency_hoarding_approve_applications.from_date",
            "agency_hoarding_approve_applications.to_date",
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
            'agency_hoarding_approve_applications.no_of_hoarding as totalHoarding',
            'agency_hoarding_approve_applications.purpose',
            'agency_hoarding_approve_applications.advertiser',
            'agency_hoarding_approve_applications.mobile_no as mobileNo',
            'agency_hoarding_approve_applications.payment_status',
            DB::raw("CASE 
            WHEN agency_hoarding_approve_applications.payment_status = '1' THEN 'Paid'
            WHEN agency_hoarding_approve_applications.payment_status = '0' THEN 'Unpaid'
            END AS paymentStatus"),
            'agency_hoarding_approve_applications.rate as amount',
            'agency_hoarding_approve_applications.location',
        )
            ->join('ulb_masters', 'ulb_masters.id', 'agency_hoarding_approve_applications.ulb_id')
            // ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', 'agency_hoarding_approve_applications.ward_id')
            ->leftjoin('agency_hoardings', 'agency_hoardings.id', 'agency_hoarding_approve_applications.id')
            ->where('agency_hoarding_approve_applications.id', $registrationId);
    }

    /**
     * | Get application details according to id
     */
    public function getApproveDetailById($id)
    {
        return AgencyHoardingApproveApplication::join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoarding_approve_applications.ulb_id')
            ->where('agency_hoarding_approve_applications.id', $id)
            ->where('agency_hoarding_approve_applications.status', '<>', 0);
    }


    public function getApprovePaidApplication()
    {
        return AgencyHoardingApproveApplication::select(
            'agency_hoarding_approve_applications.id',
            'agency_hoarding_approve_applications.application_no',
            'agency_hoardings.payment_status',
            'agency_hoardings.from_date',
            'agency_hoardings.to_date',
            'agency_hoarding_approve_applications.adv_type',
            'agency_hoarding_approve_applications.mobile_no',
            'agency_hoarding_approve_applications.apply_date',
            DB::raw("CASE 
            WHEN feedback.remarks IS NOT NULL THEN 1
            ELSE 2
            END AS VerifiedStatus"),
            'feedback.remarks'
        )
            ->join('agency_hoardings', 'agency_hoardings.id', '=', 'agency_hoarding_approve_applications.id')
            ->leftJoin('feedback', function ($join) {
                $join->on('feedback.application_id', '=', 'agency_hoardings.id')
                    ->where('feedback.status', 1);
            })
            ->where('agency_hoardings.payment_status', 1)
            ->where('agency_hoarding_approve_applications.status', true)
            ->orderByDesc('agency_hoarding_approve_applications.id');
    }
    public function saveRequestDetailsInApprove($request, $refRequest, $applicationNo, $ulbId, $registrationNo, $AgencyId)
    {
        $currentDate = Carbon::now();
        $mAgencyApproveHoarding = new AgencyHoardingApproveApplication();
        $mAgencyApproveHoarding->id                             = $AgencyId;
        $mAgencyApproveHoarding->agency_id                      = $request->agencyId;
        $mAgencyApproveHoarding->hoarding_id                    = $request->hoardingId;
        $mAgencyApproveHoarding->agency_name                    = $request->agencyName;
        // $mAgencyApproveHoarding->hoarding_type                  = $request->hoardingType;
        $mAgencyApproveHoarding->allotment_date                 = $currentDate;
        $mAgencyApproveHoarding->rate                           = $request->rate;
        $mAgencyApproveHoarding->from_date                      = $request->from;
        $mAgencyApproveHoarding->to_date                        = $request->to;
        $mAgencyApproveHoarding->user_id                        = $refRequest['empId'] ?? $refRequest['citizenId'];
        $mAgencyApproveHoarding->user_type                      = $refRequest['userType'];
        $mAgencyApproveHoarding->apply_from                     = $refRequest['applyFrom'];
        $mAgencyApproveHoarding->initiator                      = $refRequest['initiatorRoleId'];
        $mAgencyApproveHoarding->workflow_id                    = $refRequest['ulbWorkflowId'];
        $mAgencyApproveHoarding->ulb_id                         = $ulbId;
        $mAgencyApproveHoarding->finisher                       = $refRequest['finisherRoleId'];
        $mAgencyApproveHoarding->current_role_id                = $refRequest['initiatorRoleId'];
        $mAgencyApproveHoarding->application_no                 = $applicationNo;
        $mAgencyApproveHoarding->address                        = $request->residenceAddress;
        // $mAgencyApproveHoarding->doc_status                     = $request->doc_status ?? null;
        $mAgencyApproveHoarding->doc_upload_status              = $request->doc_upload_status ?? 1;                          // document is already approve offline application
        $mAgencyApproveHoarding->advertiser                     = $request->advertiser;
        $mAgencyApproveHoarding->apply_date                     = $this->_applicationDate;
        $mAgencyApproveHoarding->adv_type                       = $request->hoardingType;
        $mAgencyApproveHoarding->hoard_size_id                  = $request->squareFeetId;
        $mAgencyApproveHoarding->application_type               = $request->applicationType;
        $mAgencyApproveHoarding->size_square_feet               = $request->squarefeet;
        $mAgencyApproveHoarding->total_ballon                   = $request->Noofballons;
        $mAgencyApproveHoarding->total_vehicle                  = $request->Noofvehicle;
        $mAgencyApproveHoarding->vehicle_type_id                = $request->vehicleType;
        $mAgencyApproveHoarding->purpose                        = $request->purpose;
        $mAgencyApproveHoarding->no_of_hoarding                 = $request->Noofhoardings;
        $mAgencyApproveHoarding->mobile_no                      = $request->mobileNo;
        $mAgencyApproveHoarding->location                       = $request->location;
        $mAgencyApproveHoarding->registration_no                = $registrationNo;
        $mAgencyApproveHoarding->approve                       = 1;                                                  //static because application is already approve offline
        $mAgencyApproveHoarding->direct_hoarding               = 1;                                                  //static because application is already approve offline
        if ($request->applicationType == 'PERMANANT') {
            $mAgencyApproveHoarding->property_type_id                  = $request->propertyId;
        }
        $mAgencyApproveHoarding->save();
        return $mAgencyApproveHoarding->id;
    }
    /**
     * | Save the status in Active table
     */
    public function saveApproveApplicationStatus($applicationId)
    {
        return self::where('id', $applicationId)
            ->update([
                'payment_status' => 1
            ]);
    }
    /**
     * 
     */
    public function checkdtlsById($agencyId)
    {
        return self::where('id', $agencyId)
            ->where('status', 1)
            ->where('payment_status', 0)
            ->first();
    }
    /**
     * 
     */
    public function checkdtlsByIds($agencyId)
    {
        return self::where('id', $agencyId)
            ->where('status', 1)
            ->first();
    }
    /**
     * get details of approve applications 
     */
    public function getApproveDetails($request)
    {
        return self::select(
            'agency_hoarding_approve_applications.from_date',
            'agency_hoarding_approve_applications.to_date',
            'agency_hoarding_approve_applications.advertiser',
            'ulb_masters.ulb_name',
            'wf_roles.role_name AS current_role_name',
            // 'hoarding_masters.ward_id',
            'hoarding_masters.address',
            // 'ulb_ward_masters.ward_name',
            // 'm_circle.circle_name as zone_name',
            'agency_masters.agency_name as agencyName',
            'agency_hoarding_approve_applications.registration_no',
            'agency_hoarding_approve_applications.allotment_date',
            'agency_hoarding_approve_applications.purpose',
            'agency_hoarding_approve_applications.adv_type',
            'agency_hoarding_approve_applications.application_type',
            'agency_hoarding_approve_applications.total_vehicle',
            'measurement_sizes.measurement',
            'agency_hoarding_approve_applications.total_ballon',
            'hoarding_rates.size',
            'agency_hoarding_approve_applications.size_square_feet',
            'agency_hoarding_approve_applications.application_no',
            'agency_hoarding_approve_applications.no_of_hoarding',
            'agency_hoarding_approve_applications.direct_hoarding',
            'agency_hoarding_approve_applications.mobile_no'

        )
            ->leftjoin('agency_masters', 'agency_masters.id', 'agency_hoarding_approve_applications.agency_id')
            ->leftjoin('hoarding_masters', 'hoarding_masters.id', 'agency_hoarding_approve_applications.hoarding_id')
            ->leftjoin('wf_roles', 'wf_roles.id', '=', 'agency_hoarding_approve_applications.current_role_id')

            ->leftJoin('measurement_sizes', function ($join) {
                $join->on('measurement_sizes.id', '=', 'agency_hoarding_approve_applications.hoard_size_id')
                    ->where('measurement_sizes.status', 1);
            })
            ->leftJoin('ulb_ward_masters', function ($join) {
                $join->on('ulb_ward_masters.id', '=', 'hoarding_masters.ward_id')
                    ->where('ulb_ward_masters.status', 1);
            })
            ->leftJoin('hoarding_rates', function ($join) {
                $join->on('hoarding_rates.id', '=', 'agency_hoarding_approve_applications.hoard_size_id')
                    ->where('hoarding_rates.status', 1);
            })


            ->leftJoin('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoarding_approve_applications.ulb_id')
            ->where('agency_hoarding_approve_applications.id', $request->applicationId)
            ->where('agency_hoarding_approve_applications.status', true)
            ->where('agency_hoarding_approve_applications.approve', 1)
            ->first();
    }
    /**
     * get details of approve applications 
     */
    public function getApproveDetail($applicationId)
    {
        return self::select(
            'agency_hoarding_approve_applications.id',
            'agency_hoarding_approve_applications.from_date',
            'agency_hoarding_approve_applications.to_date',
            'agency_hoarding_approve_applications.advertiser',
            'ulb_masters.ulb_name',
            'wf_roles.role_name AS current_role_name',
            'hoarding_masters.ward_id',
            'hoarding_masters.address',
            'ulb_ward_masters.ward_name',
            'm_circle.circle_name as zone_name',
            'agency_masters.agency_name as agencyName',
            'agency_hoarding_approve_applications.registration_no',
            'agency_hoarding_approve_applications.allotment_date',
            'agency_hoarding_approve_applications.purpose',
            'agency_hoarding_approve_applications.adv_type',
            'agency_hoarding_approve_applications.application_type',
            'agency_hoarding_approve_applications.total_vehicle',
            'measurement_sizes.measurement',
            'agency_hoarding_approve_applications.total_ballon',
            'hoarding_rates.size',
            'agency_hoarding_approve_applications.size_square_feet',
            'agency_hoarding_approve_applications.application_no',
            'agency_hoarding_approve_applications.no_of_hoarding',
            'agency_hoarding_approve_applications.direct_hoarding'

        )
            ->leftjoin('agency_masters', 'agency_masters.id', 'agency_hoarding_approve_applications.agency_id')
            ->leftjoin('hoarding_masters', 'hoarding_masters.id', 'agency_hoarding_approve_applications.hoarding_id')
            ->leftjoin('wf_roles', 'wf_roles.id', '=', 'agency_hoarding_approve_applications.current_role_id')
            ->leftJoin('measurement_sizes', function ($join) {
                $join->on('measurement_sizes.id', '=', 'agency_hoarding_approve_applications.hoard_size_id')
                    ->where('measurement_sizes.status', 1);
            })
            ->leftJoin('ulb_ward_masters', function ($join) {
                $join->on('ulb_ward_masters.id', '=', 'hoarding_masters.ward_id')
                    ->where('ulb_ward_masters.status', 1);
            })
            ->leftJoin('hoarding_rates', function ($join) {
                $join->on('hoarding_rates.id', '=', 'agency_hoarding_approve_applications.hoard_size_id')
                    ->where('hoarding_rates.status', 1);
            })


            ->leftJoin('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoarding_approve_applications.ulb_id')
            ->where('agency_hoarding_approve_applications.id', $applicationId)
            ->where('agency_hoarding_approve_applications.status', true)
            ->where('agency_hoarding_approve_applications.approve', 1)
            ->first();
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
}

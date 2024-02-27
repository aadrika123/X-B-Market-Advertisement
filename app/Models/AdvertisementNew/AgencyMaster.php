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

class AgencyMaster extends Model
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

    public function addAgency($request)
    {
        $agencyMaster = new AgencyMaster();
        $agencyMaster->agency_name                =  $request->agencyName;
        $agencyMaster->agency_code                =  $request->agencyCode;
        $agencyMaster->corresponding_address      =  $request->correspondingAddress;
        $agencyMaster->contact_person             =  $request->contactPerson;
        $agencyMaster->mobile                     =  $request->mobileNo;
        $agencyMaster->pan_no                     =  $request->panNo;
        $agencyMaster->gst_no                     =  $request->gstNo;
        $agencyMaster->save();
        return $agencyMaster->id;
    }
    # add data 
    public function createData($metaReqs)
    {
        $agency = AgencyMaster::create($metaReqs);
        return $agency->id;
    }

    /**
     * GET ALL AGENCY
     */
    public function getaLL()
    {
        return self::where('status', '1')
            ->orderByDesc('id')
            ->get();
    }
    /**
     * | Deactivate the consumer Demand
     * | Demand Ids will be in array
     * | @param DemandIds
     */
    public function updateAgencydtl($metaRequest, $agencyId)
    {
        AgencyMaster::where('id', $agencyId)
            ->update($metaRequest);
    }
    /**
     * 
     */
    public function checkAgencyById($agencyId)
    {
        return self::where('id', $agencyId)
            ->where('status', 1)
            ->first();
    }
    # update status 
    public function updateStatus($agencyId)
    {
        return self::where('id', $agencyId)
            ->update([
                'status' => 0
            ]);
    }
    public function getagencyDetails($email){
        return self::select(
            'agency_masters.*',
            'hoarding_masters.*',
            'm_circle.circle_name as zone_name',
            'ulb_ward_masters.ward_name'
        )
        ->join('hoarding_masters', 'hoarding_masters.agency_id', '=', 'agency_masters.id')
        ->join('m_circle', 'hoarding_masters.zone_id', '=', 'm_circle.id')
        ->join('ulb_ward_masters','ulb_ward_masters.id','=','hoarding_masters.ward_id')
        ->where('agency_masters.email', $email)
        ->where('agency_masters.status', 1)
        ->where('hoarding_masters.status', 1)
        ->get();
    }
    /**
    get hoarding address
     */
    public function agencyhoardingAddress($email){
        return self::select(
            'agency_masters.id as agencyId',
            'agency_masters.agency_name',
            'hoarding_masters.id',
            'hoarding_masters.address',
        )
        ->join('hoarding_masters', 'hoarding_masters.agency_id', '=', 'agency_masters.id')
        ->where('agency_masters.email', $email)
        ->where('agency_masters.status', 1)
        ->where('hoarding_masters.status', 1)
        ->get();
    }
    /**
     
     */
    public function getByItsDetailsV2($req, $key, $refNo)
    {
        return HoardingMaster::select("hoarding_masters.*")
            ->where('hoarding_masters.status', 1)
            ->where('hoarding_masters.' . $key, 'LIKE', '%' . $refNo . '%');
    }
}
    

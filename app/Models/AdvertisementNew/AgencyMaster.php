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
}

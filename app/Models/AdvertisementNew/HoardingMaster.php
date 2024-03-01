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

class HoardingMaster extends Model
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
    /**
     * GET ALL hoarding
     */
    public function getaLL()
    {
        return self::where('status', '1')
            ->orderByDesc('id')
            ->get();
    }


    /**
     * | Get Agency Details by application id
     */
    public function getAgencyDetails($appId)
    {
        return HoardingMaster::select('*')
            ->where('id', $appId)
            ->first();
    }

    public function creteData($metaReqs)
    {
        HoardingMaster::create($metaReqs);
    }

    /**
     * GET ALL AGENCY
     */
    public function getaLLHording()
    {
        return self::select(
            'hoarding_masters.id',
            'hoarding_masters.hoarding_no',
            'hoarding_types.type as hoarding_type',
            'hoarding_types.id as hoardingId',
            'hoarding_masters.length',
            'hoarding_masters.width',
            'm_circle.circle_name as zone_name',
            'm_circle.id as zoneId',
            'ulb_ward_masters.id as wardId',
            'ulb_ward_masters.ward_name',
            'agency_masters.agency_name',
            'hoarding_masters.address'
        )
            ->leftjoin('agency_masters', 'agency_masters.id', 'hoarding_masters.agency_id')
            ->join('m_circle', 'm_circle.id', 'hoarding_masters.zone_id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', 'hoarding_masters.ward_id')
            ->join('hoarding_types', 'hoarding_types.id', 'hoarding_masters.hoarding_type_id')
            ->where('hoarding_masters.status', 1)
            ->orderByDesc('hoarding_masters.id')
            ->get();
    }
    public function updateHoarddtl($metaRequest, $agencyId)
    {
        HoardingMaster::where('id', $agencyId)
            ->update($metaRequest);
    }
    /**
     * 
     */
    public function checkHoardById($agencyId)
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
    /**
     * |assign agency hoarding
     */
    public function assignAgency($roleId, $userId)
    {
        return self::where('id', $userId)
            ->update([
                'agency_id' => $roleId
            ]);
    }
}

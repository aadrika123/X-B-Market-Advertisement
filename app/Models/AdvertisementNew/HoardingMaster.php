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
     * | Get Agency Details by application id
     */
    public function getAgencyDetails($appId)
    {
        return HoardingMaster::select('*')
            ->where('id', $appId)
            ->first();
    }

    public function creteData($metaReqs){
        HoardingMaster::create($metaReqs);
    }
   
     /**
     * GET ALL AGENCY
     */
    public function getaLLHording()
    {
        return self::where('status', 1)
            ->orderBy('id')
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
    public function checkHoardById($agencyId){
        return self::where('id',$agencyId)
        ->where('status',1)
        ->first();
    }
     # update status 
     public function updateStatus($agencyId){
        return self ::where('id',$agencyId)
        ->update([
           'status'=> 0
        ]);
    }
    
}

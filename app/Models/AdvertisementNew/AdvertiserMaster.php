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

class AdvertiserMaster extends Model
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
    public function creteData($metaReqs){
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
    public function checkdtlsById($agencyId){
        return self::where('id',$agencyId)
        ->where('status',1)
        ->first();
    }

    public function updatedtl($metaRequest, $agencyId)
    {
        self::where('id', $agencyId)
            ->update($metaRequest);
    }
     # update status 
     public function updateStatus($agencyId){
        return self ::where('id',$agencyId)
        ->update([
           'status'=> 0
        ]);
    }

}

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
class BrandMaster extends Model
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
        BrandMaster::create($metaReqs);
    }
/**
     * GET ALL AGENCY
     */
    public function getaLLbrand()
    {
        return self::where('status', '1')
        ->orderByDesc('id')
        ->get();
    }
    /**
     * 
     */
    public function checkBrandById($agencyId){
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

<?php

namespace App\Models\AdvertisementNew;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

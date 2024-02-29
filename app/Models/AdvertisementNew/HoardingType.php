<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HoardingType extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $_applicationDate;

    // Initializing construction
    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
    }
    # get hoarding type 
    public function getHoardingType(){
        return self::select('id','type')
        ->where('status',1)
        ->get();
    }
}

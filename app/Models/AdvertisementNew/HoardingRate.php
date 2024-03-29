<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoardingRate extends Model
{
    use HasFactory;
    public function getHoardSizerate($applicationId)
    {
        return self::select('id as temId', 'per_day_rate')
            ->where('id', $applicationId)
            ->where('status', 1)
            ->first();
    }
    #get size of temporary advertisement
    public function getHoardingSize($advertisementTypeId)
    {
        return self::select('id as sizeId', 'size')
            ->where('id',$advertisementTypeId)
            ->where('status', 1)
            ->where('size','<>',null)
            ->orderBy('sizeId','Asc')
            ->get();
    }
    public function getSizeByAdvertismentType($advertisementType)
    {
        return self::select('id as temId', 'per_day_rate','per_month','per_sq_rate')
            ->where('adv_type', $advertisementType)
            ->where('status', 1)
            ->first();
    }
}

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
    public function getHoardingSize()
    {
        return self::select('id as sizeId', 'size')
            ->where('status', 1)
            ->orderby('id', 'desc')
            ->get();
    }
}

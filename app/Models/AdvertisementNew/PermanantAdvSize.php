<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermanantAdvSize extends Model
{
    use HasFactory;
    #get measurement data 
    public function getPerSqftById($propertyId, $squareFeetId, $advertisementType)
    {
        return self::select(
            'id',
            'advertisement_type',
            'per_square_ft'
        )
            ->where('advertisement_type', $advertisementType)
            ->where('property_type', $propertyId)
            ->where('measurement_size_id', $squareFeetId)
            ->where('status', 1)
            ->first();
    }
    # get measurement for per square feet size for LED_SCREEN_ON_MOVING_VEHICLE or LED_SCREEN
    public function getSquareFeet($advertisementType)
    {
        return self::select(
            'id',
            'advertisement_type',
            'per_square_ft'
        )
            ->where('advertisement_type', $advertisementType)
            ->where('status', 1)
            ->first();
    }
}

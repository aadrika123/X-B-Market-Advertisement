<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryHoardingType extends Model
{
    use HasFactory;
    # get hoarding type 
    public function gethoardType()
    {
        return self::select('id as temId', 'type')
            ->where('status', 1)
            ->get();
    }

    public function gethoardTypeById($advId)
    {
        return self::select('id as temId', 'type', 'per_day_rate')
            ->where('id', $advId)
            ->where('status', 1)
            ->first();
    }
}

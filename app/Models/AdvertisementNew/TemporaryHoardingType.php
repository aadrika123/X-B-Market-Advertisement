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
}

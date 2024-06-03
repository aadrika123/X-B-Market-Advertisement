<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdHoardingAddress extends Model
{
    use HasFactory;

    protected $guarded = [];

    # Save multiple addresses
    public function saveMltplAddress($agencyId, $addresses)
    {
        foreach ($addresses as $address) {
            self::create([
                'hoarding_id' => $agencyId,
                'address' => $address
            ]);
        }
    }

    # get Address 
    public function getAddress($applicationId)
    {
        return self::select(
            'ad_hoarding_addresses.address'
        )
            ->where('ad_hoarding_addresses.hoarding_id', $applicationId)
            ->where('ad_hoarding_addresses.status',1);
    }
}

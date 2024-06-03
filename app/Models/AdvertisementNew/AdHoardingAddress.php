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
}

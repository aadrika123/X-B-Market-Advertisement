<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;

/**
 * | Created on: 13-09-2023
 * | Created by: Bikash Kumar
 * | Trait Created for Gettting Shop Details
 */
trait ShopDetailsTraits
{
    /**
     * | Get Basic Details
     */
    public function generateBasicDetails($data)
    {
        return new Collection([
            ['displayString' => 'Zone', 'key' => 'zone', 'value' => $data['circle_name']],
            ['displayString' => 'Market Name', 'key' => 'marketName', 'value' => $data['market_name']],
            ['displayString' => 'Allottee Name', 'key' => 'allotteeName', 'value' => $data['allottee']],
            ['displayString' => 'Contact No', 'key' => 'contactNo', 'value' => $data['contact_no']],
            ['displayString' => 'Shop No', 'key' => 'shopeNo', 'value' => $data['shop_no']],
            ['displayString' => 'Present Occupier', 'key' => 'presentOccupier', 'value' => $data['present_occupier']],
            ['displayString' => 'Shop Type', 'key' => 'shopType', 'value' => $data['shop_type']],
        ]);
    }
}

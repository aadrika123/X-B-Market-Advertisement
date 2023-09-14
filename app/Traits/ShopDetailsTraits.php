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
            ['displayString' => 'Zone', 'key' => 'zoneName', 'value' => $data['circle_name']],
            ['displayString' => 'Market Name', 'key' => 'marketName', 'value' => $data['market_name']],
            ['displayString' => 'Allottee Name', 'key' => 'allotteeName', 'value' => $data['allottee']],
            ['displayString' => 'Shop No', 'key' => 'shopeNo', 'value' => $data['shop_no']],
            ['displayString' => 'Present Occupier', 'key' => 'presentOccupier', 'value' => $data['present_occupier']],
            ['displayString' => 'Shop Type', 'key' => 'shopType', 'value' => $data['shop_type']],
            ['displayString' => 'Construction Type', 'key' => 'ConstructionType', 'value' => $data['construction_type']],
            // ['displayString' => 'Rule', 'key' => 'rule', 'value' => $data['rule']],
            // ['displayString' => 'Entity Name', 'key' => 'entityName', 'value' => $data['entity_name']],
            // ['displayString' => 'Entity Address', 'key' => 'entityAddress', 'value' => $data['entity_address']],
            // ['displayString' => 'Residential Address', 'key' => 'residentialAddress', 'value' => $data['residential_address']],
            // ['displayString' => 'Licence Year', 'key' => 'licenceYear', 'value' => $data['licenseYear']],
            // ['displayString' => 'Email', 'key' => 'email', 'value' => $data['email']],
            // ['displayString' => 'Mobile No', 'key' => 'moibileNo', 'value' => $data['mobile']],
            // ['displayString' => 'Trade Licence No', 'key' => 'tradeLicenseNo', 'value' => $data['trade_license_no']],
            // ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $data['holding_no']],
            // ['displayString' => 'Longitude', 'key' => 'longitude', 'value' => $data['longitude']],
            // ['displayString' => 'Latitude', 'key' => 'Latitude', 'value' => $data['latitude']],
            // ['displayString' => 'Floor Area', 'key' => 'floorArea', 'value' => $data['floor_area']],
            // ['displayString' => 'Aadhar Card', 'key' => 'aadharCard', 'value' => $data['aadhar_card']],
            // ['displayString' => 'Pan Card', 'key' => 'panCard', 'value' => $data['pan_card']],
            // ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data['application_type']],
            // ['displayString' => 'Ward NO', 'key' => 'wardNo', 'value' => $data['ward_no']],
            // ['displayString' => 'Permanent Ward No', 'key' => 'permanentwardNo', 'value' => $data['permanent_ward_no']],
            // ['displayString' => 'Entity Ward No', 'key' => 'entitywardNo', 'value' => $data['entity_ward_no']],
            // ['displayString' => 'Hall Type', 'key' => 'hallType', 'value' => $data['halltype']],
            // ['displayString' => 'Organization Type', 'key' => 'organizationType', 'value' => $data['organizationtype']],
            // ['displayString' => 'Remarks', 'key' => 'remarks', 'value' => $data['remarks']],
            // ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data['application_type']],
            // ['displayString' => 'No Of CCTV', 'key' => 'noOfCctv', 'value' => $data['cctv_camera']],
            // ['displayString' => 'No of Fire Extinguisher', 'key' => 'fireExtinguisher', 'value' => $data['fire_extinguisher']],
            // ['displayString' => 'No of Entry Gate', 'key' => 'entryGate', 'value' => $data['entry_gate']],
            // ['displayString' => 'No of two Wheelers Parking', 'key' => 'twoWheelersParking', 'value' => $data['two_wheelers_parking']],
            // ['displayString' => 'No of four Wheelers Parking', 'key' => 'fourWheelersParking', 'value' => $data['four_wheelers_parking']],
            // ['displayString' => 'Security Type', 'key' => 'securityType', 'value' => $data['securitytype']],
            // ['displayString' => 'Electricity Type', 'key' => 'electricityType', 'value' => $data['electricitytype']],
            // ['displayString' => 'Water Supply Type', 'key' => 'waterSupplyType', 'value' => $data['watersupplytype']],
            // ['displayString' => 'Land Deed Type', 'key' => 'landDeedType', 'value' => $data['landDeedType']],
        ]);
    }
}

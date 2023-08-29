<?php

namespace App\BLL\Advert;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * | Calculate Price On Advertisement & Market
 * | Created By- Bikash Kumar
 * | Created On 12-04-2023 
 * | Status - Closed
 */


class CalculateRate
{
    protected $_baseUrl;
    public function __construct()
    {
        $this->_baseUrl = Config::get('constants.BASE_URL');
    }

    public function generateId($token, $paramId, $ulbId)
    {
        // Generate Application No
        $reqData = [
            "paramId" => $paramId,
            'ulbId' => $ulbId
        ];
        $refResponse = Http::withToken($token)
            ->post($this->_baseUrl . 'api/id-generator', $reqData);
        $idGenerateData = json_decode($refResponse);
        return $idGenerateData->data;
    }

    public function getAdvertisementPayment($displayArea,$ulbId)
    {
        
        $rate = DB::table('adv_selfadvertisement_price_lists')
            ->select('rate')
            ->where('ulb_id', $ulbId)
            ->first()->rate;
        return $displayArea * $rate;   
    }

    public function getMovableVehiclePayment($typology, $zone, $license_from, $license_to)
    {
        $rate = DB::table('adv_typology_mstrs')
            ->select(DB::raw("case when $zone = 1 then one_day_rate_zone_a
                              when $zone = 2 then one_day_rate_zone_b
                              when $zone = 3 then one_day_rate_zone_c
                        else 0 end as rate"))
            ->where('id', $typology)
            ->first()->rate;
        $toDate = Carbon::parse($license_to);
        $fromDate = Carbon::parse($license_from);

        $noOfDays = $toDate->diffInDays($fromDate);

        return ($noOfDays * $rate);
    }


    public function getPrivateLandPayment($typology, $zone, $license_from, $license_to)
    {
        $rate = DB::table('adv_typology_mstrs')
            ->select(DB::raw("case when $zone = 1 then one_day_rate_zone_a
                              when $zone = 2 then one_day_rate_zone_b
                              when $zone = 3 then one_day_rate_zone_c
                        else 0 end as rate"))
            ->where('id', $typology)
            ->first()->rate;
        $toDate = Carbon::parse($license_to);
        $fromDate = Carbon::parse($license_from);

        $noOfDays = $toDate->diffInDays($fromDate);

        return ($noOfDays * $rate);
    }


    /**
     * | Get Hording price
     */
    public function getHordingPrice($typology_id, $zone = 'A')
    {
        return DB::table('adv_typology_mstrs')
            ->select(DB::raw("case when $zone = 1 then rate_zone_a
                              when $zone = 2 then rate_zone_b
                              when $zone = 3 then rate_zone_c
                        else 0 end as rate"))
            ->where('id', $typology_id)
            ->first()->rate;
    }

    public function calculateAmount($amount,$perAmt){
        return ($amount*$perAmt)/100;
    }

    /**
     * | Get All Types of Advertisement payment Amount
     */
    public function getPrice($area,$ulbId,$category,$licenseFrom,$licenseTO){
      $typology=DB::table('adv_typology_mstrs')
        ->select('per_day','is_sq_ft')
        ->where('id', $category)
        ->where('ulb_id', $ulbId)
        ->first();

        $toDate = Carbon::parse($licenseFrom);
        $fromDate = Carbon::parse($licenseTO);
        $noOfDays = $toDate->diffInDays($fromDate);                // Get Difference b/w no of Days or No. of Days for License

        $amount=$typology->per_day*$noOfDays;                       // Get Amount Without Square feet 
        if($typology->is_sq_ft == '1'){
            $amount=$amount*$area;                                 // Get Amount With Square feet
        }
        return $amount;
    }

}

<?php

namespace App\BLL\Advert;

use App\Models\AdvertisementNew\HoardingRate;
use App\Models\AdvertisementNew\MeasurementSize;
use App\Models\AdvertisementNew\PermanantAdvSize;
use App\Models\AdvertisementNew\TemporaryHoardingType;
use Carbon\Carbon;
use Exception;
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
    private $_squareFeet;
    private $_measurementId;
    private $_measurement;
    private $_sq_ft;
    private $_rate;
    private $_getPerSquareft;
    private $_getPermantSizeDtl;
    private $_perDayrate;
    private $_perMonth;
    private $_area;
    private $_getPerSquarerate;
    private  $_totalBallons;
    private $_numberOfVehicle;

    protected $_baseUrl;
    protected $_measurementSize;
    protected $_permanantAdvSize;
    protected $_hoardingRate;
    protected $_getData;
    protected $_onMovingVehicle;



    public function __construct()
    {
        $this->_baseUrl = Config::get('constants.BASE_URL');
        $this->_measurementSize = new MeasurementSize();
        $this->_permanantAdvSize = new PermanantAdvSize();
        $this->_hoardingRate     = new HoardingRate();
        $this->_onMovingVehicle  = new TemporaryHoardingType();
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

    public function getAdvertisementPayment($displayArea, $ulbId)
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

    public function calculateAmount($amount, $perAmt)
    {
        return ($amount * $perAmt) / 100;
    }

    /**
     * | Get All Types of Advertisement payment Amount
     */
    public function getPrice($area, $ulbId, $category, $licenseFrom, $licenseTO)
    {
        $typology = DB::table('adv_typology_mstrs')
            ->select('per_day', 'is_sq_ft')
            ->where('id', $category)
            ->where('ulb_id', $ulbId)
            ->first();

        $toDate = Carbon::parse($licenseFrom);
        $fromDate = Carbon::parse($licenseTO);
        $noOfDays = $toDate->diffInDays($fromDate);                // Get Difference b/w no of Days or No. of Days for License

        $amount = $typology->per_day * $noOfDays;                       // Get Amount Without Square feet 
        if ($typology->is_sq_ft == '1') {
            $amount = $amount * $area;                                 // Get Amount With Square feet
        }
        return $amount;
    }
    /**
     * | Calculate rate of hoarding advertisement based on request parameter
     *
     */
    public function calculateRateDtls($req)
    {
        $propertyId = $req->propertyId;
        $applicationType = $req->applicationType;
        $squareFeetId = $req->squareFeetId;
        $advertisementType = $req->advertisementType;

        $fromDate = Carbon::parse($req->from);
        $toDate = Carbon::parse($req->to);

        $monthsDifference = $fromDate->diffInMonths($toDate);
        $numberOfDays = $toDate->diffInDays($fromDate);

        // Check if the end date is on or after the start of the next month
        // $nextMonthStartDate = $fromDate->copy()->addMonths($monthsDifference);
        // if ($toDate->day >= $nextMonthStartDate->day) {
        //     $monthsDifference++;
        // }
        $monthsDifference = $toDate->diffInMonths($fromDate);

        // Ensure the minimum months difference is 1 if there is at least one day in the duration
        if ($monthsDifference <= 0 && $toDate->gt($fromDate)) {
            $monthsDifference++;
        }
        if ($applicationType == 'PERMANANT') {
            $this->_squareFeet = $this->_measurementSize->getMeasureSQfT($squareFeetId);
            if (!$this->_squareFeet) {
                throw new Exception('Square Feet not found');
            }
            // $this->_measurementId = $this->_squareFeet->id;
            // $this->_measurement =  $this->_squareFeet->measurement;
            $this->_sq_ft =  $this->_squareFeet->sq_ft;
            $this->_getPermantSizeDtl = $this->_permanantAdvSize->getPerSqftById($propertyId, $squareFeetId, $advertisementType);
            if (!$this->_getPermantSizeDtl) {
                throw new Exception('Size  not found');
            }
            $this->_getPerSquareft = $this->_getPermantSizeDtl->per_square_ft;
            $this->_rate = $monthsDifference * $this->_sq_ft * $this->_getPerSquareft;
        } else {
            switch ($advertisementType) {
                case 'TEMPORARY_ADVERTISEMENT':
                    $this->_getData = $this->_hoardingRate->getHoardSizerate($squareFeetId);
                    if (!$this->_getData) {
                        throw new Exception(' not found');
                    }
                    $this->_perDayrate = $this->_getData->per_day_rate;
                    if ($numberOfDays > 3) {
                        throw new Exception('Days should be less then 3 days ');
                    }
                    $this->_rate = $numberOfDays * $this->_perDayrate;
                    break;
                case 'LAMP_POST':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_perMonth =  $this->_getData->per_month;
                    $this->_rate = $monthsDifference * $this->_perMonth;
                    break;
                case 'ABOVE_KIOX_ADVERTISEMENT':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_perMonth =  $this->_getData->per_month;
                    $this->_rate = $monthsDifference * $this->_perMonth;
                    break;
                case 'COMPASS_CANTILEVER':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_getPerSquarerate =  $this->_getData->per_sq_rate;
                    $this->_area  = $req->squarefeet;
                    $this->_rate = $this->_area * $this->_getPerSquarerate * $monthsDifference;
                    break;
                case 'AD_POL':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_area  = 4 * 6;
                    $this->_rate = $monthsDifference * $this->_area * 250;                                                        // STATIC FOR ONE YEAR BECAUSE OF UNIQUE SIZE AREA
                    break;
                case 'GLOSSINE_BOARD':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_getPerSquarerate =  $this->_getData->per_sq_rate;
                    $this->_area  = $req->squarefeet;
                    $this->_rate = $this->_area * $this->_getPerSquarerate * $monthsDifference;
                    break;
                case 'ROAD_SHOW_ADVERTISING':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_perDayrate =  $this->_getData->per_day_rate;
                    $this->_rate = $numberOfDays *  $this->_perDayrate;
                    break;
                case 'ADVERTISEMENT_ON_THE_WALL':
                    $this->_area  = $req->squarefeet;
                    $this->_rate = $monthsDifference * $this->_area * 2.50;
                    break;
                case 'CITY_BUS_STOP':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_getPerSquarerate =  $this->_getData->per_sq_rate;
                    $this->_area  = $req->squarefeet;
                    $this->_rate = $monthsDifference * $this->_area * $this->_getPerSquarerate;
                    break;
                case 'ADVERTISEMENT_ON_THE_CITY_BUS':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_getPerSquarerate =  $this->_getData->per_sq_rate;
                    $this->_area  = $req->squarefeet;
                    $this->_rate = $monthsDifference * $this->_area * $this->_getPerSquarerate;
                    break;
                case 'ADVERTISEMENT_ON_BALLONS':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_perDayrate =  $this->_getData->per_day_rate;
                    $this->_totalBallons = $req->Noofballons;
                    $this->_rate = $numberOfDays *  $this->_totalBallons *  $this->_perDayrate;
                    break;
                case 'ADVERTISEMENT_ON_MOVING_VEHICLE':
                    $this->_getData =    $this->_onMovingVehicle->gethoardTypeById($req->vehicleType);
                    $this->_perDayrate = $this->_getData->per_day_rate; 
                    $this->_numberOfVehicle = $req->Noofvehicle;
                    $this->_rate = $this->_numberOfVehicle * $numberOfDays *  $this->_perDayrate;
                    break;
                default:
                    throw new Exception('Invalid advertisement type');
                    break;
            }
        }
        return   [
            'rate' =>  $this->_rate
        ];
    }
    public function calculateRateDtl($req)
    {
        // Extract request parameters
        $propertyId = $req->propertyId;
        $applicationType = $req->applicationType;
        $squareFeetId = $req->squareFeetId;
        $advertisementType = $req->advertisementType;
        $fromDate = Carbon::parse($req->from);
        $toDate = Carbon::parse($req->to);

        // Calculate months difference and number of days
        $monthsDifference = $toDate->diffInMonths($fromDate);
        $numberOfDays = $toDate->diffInDays($fromDate);

        // Adjust months difference if needed
        if ($monthsDifference <= 0 && $toDate->gt($fromDate)) {
            $monthsDifference++;
        }

        // Calculate rate based on application type and advertisement type
        if ($applicationType == 'PERMANENT') {
            $this->calculatePermanentRate($propertyId, $squareFeetId, $advertisementType, $monthsDifference);
        } else {
            $this->calculateTemporaryRate($advertisementType, $monthsDifference, $numberOfDays, $req);
        }

        return ['rate' => $this->_rate];
    }

    private function calculatePermanentRate($propertyId, $squareFeetId, $advertisementType, $monthsDifference)
    {
        // Fetch required data
        $this->_squareFeet = $this->_measurementSize->getMeasureSQfT($squareFeetId);
        if (!$this->_squareFeet) {
            throw new Exception('Square Feet not found');
        }

        // Calculate rate for permanent advertisement
        $this->_sq_ft = $this->_squareFeet->sq_ft;
        $this->_getPermantSizeDtl = $this->_permanantAdvSize->getPerSqftById($propertyId, $squareFeetId, $advertisementType);
        if (!$this->_getPermantSizeDtl) {
            throw new Exception('Size not found');
        }
        $this->_getPerSquareft = $this->_getPermantSizeDtl->per_square_ft;
        $this->_rate = $monthsDifference * $this->_sq_ft * $this->_getPerSquareft;
    }

    private function calculateTemporaryRate($advertisementType, $monthsDifference, $numberOfDays, $req)
    {
        // Calculate rate for temporary advertisement based on advertisement type
        switch ($advertisementType) {
            case 'TEMPORARY_ADVERTISEMENT':
                $this->_getData = $this->_hoardingRate->getHoardSizerate($req->squareFeetId);
                // Remaining code for temporary advertisement types...
                break;
            case 'LAMP_POST':
            case 'ABOVE_KIOX_ADVERTISEMENT':
                // Code for other temporary advertisement types...
                break;
            default:
                throw new Exception('Invalid advertisement type');
                break;
        }
    }
}

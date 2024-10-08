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

    #===================================== CALCULATE RATE FOR NEW ADVERTISEMENT ========================# 
    /**
     * | Calculate rate of hoarding advertisement based on request parameter
     *  request
     *
     */
    public function calculateRateDtls($req)
    {
        # initialise  the vaiable 
        $propertyId          = $req->propertyId;
        $applicationType     = $req->applicationType;
        $squareFeetId        = $req->squareFeetId;
        $advertisementType   = $req->advertisementType;
        $noOfHoardings       = $req->Noofhoardings;

        $fromDate = Carbon::parse($req->from);
        $toDate = Carbon::parse($req->to);
        $toDates = $toDate->addDay();
        $monthsDifference = $fromDate->diffInMonths($toDates);                                                 //month diference
        $numberOfDays = $toDate->diffInDays($fromDate);                                                       // days difference    

        #Application Type Permnanant
        if ($applicationType == 'PERMANANT' &&  $advertisementType == 'ABOVE_GROUND') {                                        // application type = PERMANANT
            $this->_squareFeet = $this->_measurementSize->getMeasureSQfT($squareFeetId);                    // GET SQUARE FEET SIZE 
            if (!$this->_squareFeet) {
                throw new Exception('Square Feet not found');
            }
            $this->_sq_ft =  $this->_squareFeet->sq_ft ?? $req->squarefeet;
            $this->_getPermantSizeDtl = $this->_permanantAdvSize->getPerSqftById($propertyId, $squareFeetId, $advertisementType);
            if (!$this->_getPermantSizeDtl) {
                throw new Exception('Size  not found');
            }
            $this->_getPerSquareft = $this->_getPermantSizeDtl->per_square_ft;
            $this->_rate = $monthsDifference * $this->_sq_ft * $this->_getPerSquareft * $noOfHoardings;                           // RATE OF PERMANANT APPLICATION TYPE 
        } elseif ($applicationType == 'PERMANANT' && $advertisementType == 'ON_THE_BUILDING') {
            $this->_squareFeet = $this->_measurementSize->getMeasureSQfT($squareFeetId);                        // GET SQUARE FEET SIZE 
            if (!$this->_squareFeet) {
                throw new Exception('Square Feet not found');
            }
            $this->_sq_ft =  $this->_squareFeet->sq_ft ?? $req->squarefeet * $noOfHoardings;
            $this->_getPermantSizeDtl = $this->_permanantAdvSize->getPerSqftById($propertyId, $squareFeetId, $advertisementType);
            if (!$this->_getPermantSizeDtl) {
                throw new Exception('Size  not found');
            }
            $this->_getPerSquareft = $this->_getPermantSizeDtl->per_square_ft;
            $this->_rate = $monthsDifference * $this->_sq_ft * $this->_getPerSquareft * $noOfHoardings;                                  // RATE OF PERMANANT APPLICATION TYPE 
        } elseif ($applicationType == 'PERMANANT' && $advertisementType == 'LED_SCREEN_ON_MOVING_VEHICLE') {
            $this->_sq_ft = $req->squarefeet;
            $this->_getPermantSizeDtl = $this->_permanantAdvSize->getSquareFeet($advertisementType);
            if (!$this->_getPermantSizeDtl) {
                throw new Exception('Size  not found');
            }
            if ($monthsDifference == 0) {
                throw new Exception('Its Apply for month  Only not days');
            }
            $this->_getPerSquareft = $this->_getPermantSizeDtl->per_square_ft;
            $this->_rate = $monthsDifference * $this->_sq_ft * $this->_getPerSquareft * $noOfHoardings;                                  // RATE OF PERMANANT APPLICATION TYPE 
        } elseif ($applicationType == 'PERMANANT' && $advertisementType == 'LED_SCREEN') {
            $this->_sq_ft = $req->squarefeet;

            if ($monthsDifference == 0) {
                throw new Exception('Its Apply for month  Only not days');
            }
            $this->_getPermantSizeDtl = $this->_permanantAdvSize->getSquareFeet($advertisementType);
            if (!$this->_getPermantSizeDtl) {
                throw new Exception('Size  not found');
            }
            $this->_getPerSquareft = $this->_getPermantSizeDtl->per_square_ft;
            $this->_rate = $monthsDifference * $this->_sq_ft * $this->_getPerSquareft * $noOfHoardings;                                  // RATE OF PERMANANT APPLICATION TYPE 

        } elseif ($applicationType == 'TEMPORARY') {
            switch ($advertisementType) {                                                                        //  HERE START TO CALCULATING RATE FOR TEMPORARAY APPLICATION TYPE                                                                 
                case 'TEMPORARY_ADVERTISEMENT':                                                                  // DIFFERENT ADVERTISEMENT TYPE APPLY BY THE AGENCY 
                    $this->_getData = $this->_hoardingRate->getHoardSizerate($squareFeetId);
                    if (!$this->_getData) {
                        throw new Exception(' Data not found');
                    }
                    $this->_perDayrate = $this->_getData->per_day_rate;
                    if ($numberOfDays > 3) {
                        throw new Exception('You Can Apply For Only Three Days');
                    }

                    $this->_rate = $numberOfDays * $this->_perDayrate * $noOfHoardings;                                            // THIS RATE TEMPORARY ADVERTISEMENT TYPE 
                    break;
                case 'LAMP_POST':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_perMonth =  $this->_getData->per_month;
                    if ($monthsDifference == 0) {
                        throw new Exception('You Can Apply For  Month');
                    }
                    $this->_rate = $monthsDifference * $this->_perMonth * $noOfHoardings;                                          // THIS RATE FOR ADVERTISEMENT ON LAMP POST
                    break;
                case 'ABOVE_KIOX_ADVERTISEMENT':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    if ($monthsDifference == 0) {
                        throw new Exception('You Can Apply For  Month');
                    }
                    $this->_perMonth =  $this->_getData->per_month;
                    $this->_rate = $monthsDifference * $this->_perMonth * $noOfHoardings;                                          // THIIS RATE FOR ADVERTISEMENT ON KIOX
                    break;
                case 'COMPASS_CANTILEVER':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_getPerSquarerate =  $this->_getData->per_sq_rate;
                    if ($monthsDifference == 0) {
                        throw new Exception('You Can Apply For  Month');
                    }
                    $this->_area  = $req->squarefeet;
                    $this->_rate = $this->_area * $this->_getPerSquarerate * $monthsDifference * $noOfHoardings;                   // THIS RATE FOR ADVERTISEMENT ON COMPASS CANTIELEVER
                    break;
                case 'AD_POL':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_area  = 4 * 6;                                                                          // FIXED SIE FOR ADVERTISEMENT ON AD POL
                    $this->_getPerSquarerate =  $this->_getData->per_sq_rate;
                    if ($monthsDifference != 12) {
                        throw new Exception('Its Apply for One Year Only');
                    }
                    $this->_rate =  $this->_area * $this->_getPerSquarerate * $noOfHoardings;                                      // THIS IS RATE FOR ADVERTISEMENT ON POL                                       
                    break;
                case 'GLOSSINE_BOARD':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_getPerSquarerate =  $this->_getData->per_sq_rate;
                    $this->_area  = $req->squarefeet;
                    if ($monthsDifference == 0) {
                        throw new Exception('You Can Apply For  Month');
                    }
                    $this->_rate = $this->_area * $this->_getPerSquarerate * $monthsDifference * $noOfHoardings;                  // THIS RATE FOR ADVERTSIMENT ON GLOSSINE BOARD OF COMPANY
                    break;
                case 'ROAD_SHOW_ADVERTISING':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_perDayrate =  $this->_getData->per_day_rate;
                    $this->_rate = $numberOfDays *  $this->_perDayrate;
                    break;
                case 'ADVERTISEMENT_ON_THE_WALL':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_getPerSquarerate =  $this->_getData->per_sq_rate;
                    $this->_area  = $req->squarefeet;
                    if ($monthsDifference == 0) {
                        throw new Exception('You Can Apply For  Month');
                    }
                    $this->_rate = $monthsDifference * $this->_area *  $this->_getPerSquarerate * $noOfHoardings;
                    break;
                case 'CITY_BUS_STOP':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    $this->_getPerSquarerate =  $this->_getData->per_sq_rate;
                    $this->_area  = $req->squarefeet;
                    if ($monthsDifference != 12) {
                        throw new Exception('Its Apply for One Year Only');
                    }
                    $this->_rate =  $this->_area * $this->_getPerSquarerate * $noOfHoardings;
                    break;
                case 'ADVERTISEMENT_ON_THE_CITY_BUS':
                    $this->_getData =  $this->_hoardingRate->getSizeByAdvertismentType($advertisementType);
                    if ($monthsDifference != 12) {
                        throw new Exception('Its Apply for One Year Only');
                    }
                    $this->_getPerSquarerate =  $this->_getData->per_sq_rate;
                    $this->_area  = $req->squarefeet;
                    $this->_rate =  $this->_area * $this->_getPerSquarerate * $noOfHoardings;
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
                    $this->_rate = $this->_numberOfVehicle * $numberOfDays *  $this->_perDayrate * $noOfHoardings;
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
}

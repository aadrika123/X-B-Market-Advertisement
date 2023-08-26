<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master\MMarket;
use App\Models\Rentals\ShopConstruction;
use Illuminate\Support\Facades\Validator;
use Exception;

class MarketController extends Controller
{
    private $_mMarket;

    public function __construct()
    {
        $this->_mMarket = new MMarket();
    }

    // Add records
    public function store(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'circleId' => 'required|integer',
            'marketName' => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $exists = $this->_mMarket->getMarketNameByCircleId($req->marketName, $req->circleId);
            if (collect($exists)->isNotEmpty())
                throw new Exception("Market According To Circle Id Already Existing");

            $metaReqs = [
                'circle_id' => $req->circleId,
                'market_name' => $req->marketName
            ];

            $this->_mMarket->create($metaReqs);

            return responseMsgs(true, "Successfully Saved", [$metaReqs], "055301", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {

            return responseMsgs(false, $e->getMessage(), [], "055301", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    // Edit records
    public function edit(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'         => 'required|integer',
            'circleId'   => 'required|integer',
            'marketName' => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);

        try {

            $exists = $this->_mMarket->getMarketNameByCircleId($req->marketName, $req->circleId);
            if (collect($exists)->where('id', '!=', $req->id)->isNotEmpty())
                throw new Exception("Market According To Circle Id Already Existing");

            $metaReqs = [
                'circle_id'   => $req->circleId,
                'market_name' => $req->marketName
            ];

            $market = $this->_mMarket->findOrFail($req->id);
            $market->update($metaReqs);

            return responseMsgs(true, "Successfully Saved", [$metaReqs], "055302", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {

            return responseMsgs(false, $e->getMessage(), [], "055302", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    //find by Circle Id
    public function getMarketByCircleId(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'circleId' => 'required|integer'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $Market = $this->_mMarket->getMarketByCircleId($req->circleId);
            if (collect($Market)->isEmpty())
                throw new Exception("Market According To Circle Id Does Not Exist");
            return responseMsgs(true, "", $Market, "055303", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055303", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    //retireve all records
    public function retireveAll(Request $req)
    {
        try {
            $circle = $this->_mMarket->getAllActive();
            if (collect($circle)->isEmpty())
                throw new Exception("No Data Found");
            return responseMsgs(true, "", $circle, "055304", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055304", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    //delete records
    public function delete(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'  => 'required|integer',
            'isActive' => 'required|bool'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);

        try {
            if (isset($req->isActive)) {
                $isActive = $req->isActive == false ? 0 : 1;
                $metaReqs = [
                    'is_active' => $isActive
                ];
            }
            $Shops = $this->_mMarket::findOrFail($req->id);
            $Shops->update($metaReqs);
            return responseMsgs(true, "Status Updated Successfully", [], "055205", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055205", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }


    public function listConstruction(Request $req){
        try{
            $mShopConstruction=new ShopConstruction();
            $list=$mShopConstruction->listConstruction();
            return responseMsgs(true, "Construction Fetch Successfully", $list, "055205", "1.0", responseTime(), "POST", $req->deviceId);
        }catch(Exception $e){
            return responseMsgs(false, $e->getMessage(), [], "055205", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }
}

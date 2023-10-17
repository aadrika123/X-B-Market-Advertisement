<?php

namespace App\Http\Controllers\Advertisements;

use App\Http\Controllers\Controller;
use App\Models\Param\RefAdvParamstring;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AdvertisementController extends Controller
{
        /**
     * | String Parameters values
     * | @param request $req
     * | Function - 01
     */
    public function paramStrings(Request $req)
    {
        $redis = Redis::connection();
        try {
            // Variable initialization
            $mUlbId = $req->ulbId;
            $data = json_decode(Redis::get('adv_param_strings'));                                               // Get Value from Redis Cache Memory
            if (!$data) {                                                                                       // If Cache Memory is not available
                $data = array();
                $mParamString = new RefAdvParamstring();
                $strings = $mParamString->masters();
                $data['paramCategories'] = remove_null($strings->groupBy('param_category')->toArray());

                $redis->set('adv_param_strings' . $mUlbId, json_encode($data));                                 // Set Key on Param Strings
            }
            return responseMsgs(true, "Param Strings", $data, "050201", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}

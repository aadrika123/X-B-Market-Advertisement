<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AdevertisementNew\AdPaymentController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Rentals\ShopController;
use App\Models\EasebuzzPaymentReq;
use App\Models\EasebuzzPaymentResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class EasebuzzPaymentContoller extends Controller
{
    //

    private $_EasebuzzPaymentReq;
    private $_EasebuzzPaymentResponse;

    function __construct()
    {
        $this->_EasebuzzPaymentReq = new EasebuzzPaymentReq();
        $this->_EasebuzzPaymentResponse = new EasebuzzPaymentResponse();
    }

    public function callBackResponse(Request $request){
        $req_ref_no = $request->txnid;
        $requestData = $this->_EasebuzzPaymentReq->where("req_ref_no",$req_ref_no)->orderBy("id","DESC")->first();
        $this->_EasebuzzPaymentResponse->req_ref_no = $req_ref_no;
        $this->_EasebuzzPaymentResponse->easebuzz_payment_reqs_id = $requestData->id??null;
        $this->_EasebuzzPaymentResponse->easepayid = $request->easepayid;
        $this->_EasebuzzPaymentResponse->bank_ref_num = $request->bank_ref_num;
        $this->_EasebuzzPaymentResponse->pay_mode = $request->card_type;
        $this->_EasebuzzPaymentResponse->bank_name = $request->bank_name;
        $this->_EasebuzzPaymentResponse->name_on_card = $request->name_on_card;
        $this->_EasebuzzPaymentResponse->bankcode = $request->bankcode;
        $this->_EasebuzzPaymentResponse->phone = $request->phone;
        $this->_EasebuzzPaymentResponse->email = $request->email;
        $this->_EasebuzzPaymentResponse->trn_date = $request->addedon;
        $this->_EasebuzzPaymentResponse->response_status = $request->status;
        $this->_EasebuzzPaymentResponse->error_message = $request->error_Message;
        $this->_EasebuzzPaymentResponse->tran_amt = $request->amount;
        $this->_EasebuzzPaymentResponse->hash_val = $request->hash;
        $this->_EasebuzzPaymentResponse->response_json = json_encode($request->all(),JSON_UNESCAPED_UNICODE);
        $this->_EasebuzzPaymentResponse->save();  
        $requestData ? $requestData->payment_status = (strtolower($request->status)=='success' ? 1 : 2) :"";         
        $refData = [
            "callBack"          => $requestData ? ($requestData->front_success_url  ? $requestData->front_success_url : Config::get("payment-constants.FRONT_URL")): Config::get("payment-constants.FRONT_URL") ,
            "UniqueRefNumber"   => $req_ref_no?? "",
            "PaymentMode"       => $request->card_type ?? ""
        ];
        if(strtolower($request->status)=='success' && $requestData){            
            $newRequest = new Request($request->all());
            $requestExtraData = json_decode($requestData->payload_json,true);
            collect($requestExtraData)->map(function($val,$index)use($newRequest){
                if(!$newRequest->has($index)){
                    $newRequest->merge([$index=>$val]);
                }
            });
            switch($requestData->module_id){
                #property
                case 1 : #code for property;
                        break;
                case 14 : #Hoarding Board;
                        $controller = App::makeWith(AdPaymentController::class);
                        $controller->easebuzzHandelResponse($newRequest);
                        break;
                case 30 : #Shop Rent;
                        $controller = App::makeWith(ShopController::class);
                        $controller->easebuzzHandelResponse($newRequest);
                        break;
                
            }
            
            // dd($newRequest->all(),$requestExtraData, $refData);
            return view('icici_payment_call_back', $refData);
        }
        $erroData = [
            "redirectUrl" => $requestData ? ($requestData->front_fail_url ? $requestData->front_fail_url:Config::get("payment-constants.FRONT_URL")) : Config::get("payment-constants.FRONT_URL"),
        ];
        if(!$requestData){
            $erroData = [
                "redirectUrl" => Config::get("payment-constants.FRONT_URL"),
            ];
        }
        return view('icic_payment_erro', $erroData);
    }
}

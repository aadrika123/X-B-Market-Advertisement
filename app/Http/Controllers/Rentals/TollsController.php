<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toll\TollValidationRequest;
use App\MicroServices\DocumentUpload;
use App\Models\Bandobastee\MarTollPriceList;
use App\Models\Rentals\MarToll;
use App\Models\Rentals\MarTollPayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TollsController extends Controller
{
    private $_mToll;

    protected $_ulbLogoUrl;

    /**
     * | Created On-14-06-2023 
     * | Author - Anshu Kumar
     * | Change By - Bikash Kumar
     */
    public function __construct()
    {
        $this->_mToll = new MarToll();
        $this->_ulbLogoUrl = Config::get('constants.ULB_LOGO_URL');                                // Logo Url for Reciept
    }

    /**
     * | Toll Payments between given two dates
     * | Function - 01
     * | API - 01
     */
    public function tollPayments(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "tollId" => "required|integer",
            "dateUpto" => "required|date|date_format:Y-m-d",
            "dateFrom" => "required|date|date_format:Y-m-d|before_or_equal:$req->dateUpto",
            "paymentMode" => "required|string",
            "remarks" => "nullable|string"
        ]);

        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), [], "055101", "1.0", responseTime(), "POST", $req->deviceId);

        try {
            // Variable Assignments
            $todayDate = Carbon::now()->format('Y-m-d');
            $mTollPayment = new MarTollPayment();

            $toll = $this->_mToll::find($req->tollId);
            if (collect($toll)->isEmpty())
                throw new Exception("Toll Not Available for this ID");
            $dateFrom = Carbon::parse($req->dateFrom);
            $dateUpto = Carbon::parse($req->dateUpto);

            // Calculation Date difference between two dates
            $diffInDays = $dateFrom->diffInDays($dateUpto);
            $noOfDays = $diffInDays + 1;
            $rate = $toll->rate;
            $payableAmt = $noOfDays * $rate;
            if ($payableAmt < 1)
                throw new Exception("Dues Not Available");

            // Payment records insert in toll payment tables
            $reqTollPayment = [
                'toll_id' => $toll->id,
                'from_date' => $req->dateFrom,
                'to_date' => $req->dateUpto,
                'amount' => $payableAmt,
                'rate' => $rate,
                'days' => $noOfDays,
                'payment_date' => $todayDate,
                'pmt_mode' => $req->paymentMode,
                'user_id' => $req->auth['id'] ?? 0,
                'ulb_id' => $toll->ulb_id,
                'remarks' => $req->remarks,
                'transaction_no' => "TRAN-" . time() . $toll->id,                                       // Generate Transaction No Using TRAN-time function in php and toll Id
                'session' => getCurrentSesstion(date('Y-m-d'))
            ];
            $createdTran = $mTollPayment->create($reqTollPayment);
            $toll->update([
                'last_payment_date' => $todayDate,
                'last_amount' => $payableAmt,
                'last_tran_id' => $createdTran->id
            ]);
            return responseMsgs(true, "Payment Successfully Done", ['paymentAmount' => $payableAmt, 'tollId' => $toll->id], "055101", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055101", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Store Toll Records
     * | Function - 02
     * | API - 02
     */
    public function store(TollValidationRequest $request)
    {
        try {
            $docUpload = new DocumentUpload;
            $relativePath = Config::get('constants.TOLL_PATH');
            if (isset($request->photograph1)) {
                $image = $request->file('photograph1');
                $refImageName = 'Toll-Photo-1' . '-' . $request->vendorName;
                $imageName1 = $docUpload->upload($refImageName, $image, $relativePath);
                $absolutePath = $relativePath;
                $imageName1Absolute = $absolutePath;
            }
            $tollNo = $this->tollIdGeneration($request->marketId);
            $marToll = [
                'circle_id'               => $request->circleId,
                'toll_no'                 => $tollNo,
                'vendor_name'             => $request->vendorName,
                'address'                 => $request->address,
                'rate'                    => $request->rate,
                'market_id'               => $request->marketId,
                'mobile'                  => $request->mobile,
                'remarks'                 => $request->remarks,
                'photograph1'             => $imageName1 ?? null,
                'photo1_absolute_path'    => $imageName1Absolute ?? null,
                'user_id'                 => $request->auth['id'],
                'ulb_id'                  => $request->auth['ulb_id'],
                'vendor_type'             => $request->vendorType,
            ];
            // return $marToll;
            $this->_mToll->create($marToll);                            // Store toll Data
            return responseMsgs(true, "Successfully Saved", $marToll, "055102", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055102", "1.0", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | ID Generation For Toll
     * | Function - 03
     */
    public function tollIdGeneration($marketId)
    {
        $idDetails = DB::table('m_market')->select('toll_counter', 'market_name')->where('id', $marketId)->first();
        $market = strtoupper(substr($idDetails->market_name, 0, 3));
        $counter = $idDetails->toll_counter + 1;
        DB::table('m_market')->where('id', $marketId)->update(['toll_counter' => $counter]);
        return $id = "TOLL-" . $market . "-" . (1000 + $idDetails->toll_counter);
    }

    /**
     * | Get List All Toll
     * | Function - 04
     * | API - 03
     */
    public function listToll(Request $req)
    {
        try {
            $mMarToll = new MarToll();
            $list = $mMarToll->getUlbWiseToll($req->auth['ulb_id']);            // Retrieve Toll Data Ulbwise from model
            $list = paginator($list, $req);                                     // Paginate Toll Data
            return responseMsgs(true, "List Fetch Successfully !!!", $list, "055103", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055103", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Update Toll Records
     * | Function - 05
     * | API - 04
     */
    public function edit(TollValidationRequest $request) //upadte
    {
        $validator = Validator::make($request->all(), [
            "id" => 'required|numeric',
            "status" => 'nullable|bool'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), [], "055104", "1.0", responseTime(), "POST", $request->deviceId);

        try {
            $relativePath = Config::get('constants.TOLL_PATH');
            $docUpload = new DocumentUpload;
            if (isset($request->photograph1)) {
                $image = $request->file('photograph1');
                $refImageName = 'Toll-Photo-1' . '-' . $request->vendorName;
                $imageName1 = $docUpload->upload($refImageName, $image, $relativePath);
                $absolutePath = $relativePath;
                $imageName1Absolute = $absolutePath;
            }

            if (isset($request->photograph2)) {
                $image = $request->file('photograph2');
                $refImageName = 'Toll-Photo-2' . '-' . $request->vendorName;
                $imageName2 = $docUpload->upload($refImageName, $image, $relativePath);
                $absolutePath = $relativePath;
                $imageName2Absolute = $absolutePath;
            }
            $marToll = [
                'circle_id' => $request->circleId,
                'vendor_name' => $request->vendorName,
                'address' => $request->address,
                'rate' => $request->rate,
                'last_payment_date' => $request->lastPaymentDate,
                'last_amount' => $request->lastAmount,
                'market_id' => $request->marketId,
                'present_length' => $request->presentLength,
                'present_breadth' => $request->presentBreadth,
                'present_height' => $request->presentHeight,
                'no_of_floors' => $request->noOfFloors,
                'trade_license' => $request->tradeLicense,
                'construction' => $request->construction,
                'utility' => $request->utility,
                'mobile' => $request->mobile,
                'remarks' => $request->remarks,
                'longitude' => $request->longitude,
                'latitude' => $request->latitude,
                'user_id' => $request->auth['id'],
                'ulb_id' => $request->auth['ulb_id'],
                'last_tran_id' => $request->lastTranId,
            ];
            if (isset($request->status)) {                  // In Case of Deactivation or Activation
                $status = $request->status == false ? 0 : 1;
                $marToll = array_merge($marToll, ['status', $status]);
            }

            if (isset($request->photograph1)) {
                $marToll = array_merge($marToll, ['photograph1', $imageName1]);
                $marToll = array_merge($marToll, ['photo1_absolute_path', $imageName1Absolute]);
            }

            if (isset($request->photograph2)) {
                $marToll = array_merge($marToll, ['photograph2', $imageName2]);
                $marToll = array_merge($marToll, ['photo2_absolute_path', $imageName2Absolute]);
            }

            $toll = $this->_mToll::findOrFail($request->id);
            $toll->update($marToll);
            return responseMsgs(true, "Update Successfully ",  [], "055104", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055104", "1.0", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Get Toll Details By Id
     * | Function - 06
     * | API - 05
     */
    public function show(Request $request)
    {
        $validator = validator::make($request->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors(), []);
        }
        try {

            $toll = $this->_mToll::find($request->id);
            if (!$toll)
                throw new Exception("No Data Found !!!");
            return responseMsgs(true, "record found", $toll, "055105", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055105", "1.0", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Get All toll records
     * | Function - 07
     * | API - 06
     */
    public function retrieve(Request $request)
    {
        try {
            $mtoll = $this->_mToll->getUlbWiseToll($request->auth['ulb_id']);
            if ($request->key)
                $mtoll = searchTollRentalFilter($mtoll, $request);
            $mtoll = paginator($mtoll, $request);
            return responseMsgs(true, "", $mtoll, "055106", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055106", "1.0", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Get All Active Toll Records
     * | Function - 08
     * | API - 07
     */
    public function retrieveActive(Request $request)
    {
        try {
            $mtoll = $this->_mToll->retrieveActive();
            return responseMsgs(true, "", $mtoll, "055107", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055107", "1.0", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Active or De-Active Tolls
     * | Function - 09
     * | API - 08
     */
    public function delete(Request $request)
    {
        $validator = validator::make($request->all(), [
            'id' => 'required|integer',
            'status' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors(), []);
        }
        try {
            if (isset($request->status)) {                                                          // In Case of Deactivation or Activation
                $status = $request->status == false ? 0 : 1;
                $metaReqs = [
                    'status' => $status
                ];
            }
            if ($request->status == '0') {
                $message = "Toll De-Activated Successfully !!!";
            } else {
                $message = "Toll Activated Successfully !!!";
            }
            $marToll = $this->_mToll::findOrFail($request->id);
            $marToll->update($metaReqs);
            return responseMsgs(true, $message, [], "055108", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055108", "1.0", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Get Toll Collection Summery
     * | Function - 10
     * | API - 09
     */
    public function getTollCollectionSummary(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => $req->fromDate == NULL ? 'nullable|date_format:Y-m-d' : 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            if ($req->fromDate == NULL) {
                $fromDate = date('Y-m-d');
                $toDate = date('Y-m-d');
            } else {
                $fromDate = $req->fromDate;
                $toDate = $req->toDate;
            }
            $mMarTollPayment = new MarTollPayment();
            $list = $mMarTollPayment->paymentList($req->auth['ulb_id'])->whereBetween('payment_date', [$fromDate, $toDate]);
            $list = paginator($list, $req);

            $list['todayCollection'] = $mMarTollPayment->todayTallCollection($req->auth['ulb_id'], date('Y-m-d'))->get()->sum('amount');
            return responseMsgs(true, "Toll Summary Fetch Successfully !!!", $list, "055109", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055109", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Toll list by Market Id
     * | Function - 11
     * | API - 10
     */
    public function listTollByMarketId(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'marketId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors(), []);
        }
        try {
            $mMarToll = new MarToll();
            $list = $mMarToll->getToll($req->marketId);
            if ($req->key)
                $list = searchTollRentalFilter($list, $req);
            $list = paginator($list, $req);
            return responseMsgs(true, "Toll List Fetch Successfully !!!", $list, "055110", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055110", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }


    /**
     * | search applocation by Mobile No., Name or , Toll No
     * | Function - 12
     * | API - 11
     */
    public function searchTollByNameOrMobile(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'key' => 'required'
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        $mMarToll = new MarToll();
        $list = $mMarToll->getUlbWiseToll($req->auth['ulb_id']);
        if ($req->key)
            $list = searchTollRentalFilter($list, $req);
        $list = paginator($list, $req);
        try {
            return responseMsgs(true, "List Fetch Successfully !!!", $list, "055111", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055111", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Toll Details By Id
     * | Function - 13
     * | API - 12
     */
    public function getTollDetailtId(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'tollId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors(), []);
        }
        try {
            $mMarToll = new MarToll();
            $list = $mMarToll->getTollDetailById($req->tollId);
            return responseMsgs(true, "Toll Details Fetch Successfully !!!", $list, "055112", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055112", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Toll Payment By Admin
     * | Function - 14
     * | API - 13
     */
    public function tollPaymentByAdmin(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate' => 'required|date_format:Y-m-d',
            'toDate' => 'required|date_format:Y-m-d',
            'tollNo' => 'required|string',
            'amount' => 'required|numeric',
            'rate' =>   'required|numeric',
            'paymentDate' => 'required|date_format:Y-m-d',
            'collectedBy' => 'required|string',
            'remarks' => "required|string",
            'image'   => 'nullable|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $docUpload = new DocumentUpload;
            $relativePath = Config::get('constants.SHOP_PATH');
            if (isset($req->image)) {
                $image = $req->file('image');
                $refImageName = 'reciept' . '-' . time();
                $imageName1 = $docUpload->upload($refImageName, $image, $relativePath);
                // $absolutePath = $relativePath;
                $imageName1Absolute = $relativePath;
                $req->merge(['reciepts' => $imageName1]);
                $req->merge(['absolutePath' => $imageName1Absolute]);
            }

            $mMarTollPayment = new MarTollPayment();
            $details = DB::table('mar_tolls')->select('*')->where('toll_no', $req->tollNo)->first();
            if (!$details)
                throw new Exception("Toll Not Found !!!");
            $tollId = $details->id;
            $months = monthDiff($req->toDate, $req->fromDate) + 1;
            $req->merge(['months' => $months]);

            $paymentId = $mMarTollPayment->addPaymentByAdmin($req, $tollId);
            $mMarToll = new MarToll();
            $mTollDetails = $mMarToll->find($tollId);
            $mTollDetails->last_tran_id = $paymentId;
            $mTollDetails->save();
            return responseMsgs(true, "Payment Accept Successfully !!!", '', "055113", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055113", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Market Toll Price List
     * | Function - 15
     * | API - 14
     */
    public function getTollPriceList(Request $req)
    {
        try {
            $mMarTollPriceList = new MarTollPriceList();
            $list = $mMarTollPriceList->getTollPriceList();
            return responseMsgs(true, "Price List Fetch Successfully !!!", $list, "055114", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055114", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Last Toll Payment Reciept
     * | Function - 16
     * | API - 15
     */
    public function getPaymentReciept(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'tollId' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $mMarToll = new MarToll();
            $reciept = $mMarToll->getReciept($req->tollId);
            $reciept->inWords = trim(getIndianCurrency($reciept->last_amount)) . " only /-";
            $reciept->ulbLogo =  $this->_ulbLogoUrl . $reciept->logo;
            $reciept->description =  "Daily Licence Reciept";
            return responseMsgs(true, "Payment Reciept Fetch Successfully !!!", $reciept, "055115", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055115", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Search Toll by name or mobile number
     * | Function - 17
     * | API - 16
     */
    public function searchToll(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'searchBy' => 'required|in:vendorName,mobileNo',
            'value' => $req->searchBy == "vendorName" ? 'required|string|' : 'required|digits:10',
        ]);

        if ($validator->fails()) {
            return  $validator->errors();
        }
        $mMarToll = new MarToll();
        $list = $mMarToll->getUlbWiseToll($req->auth['ulb_id']);
        if ($req->searchBy == 'mobileNo')
            $list = $list->where('mobile', $req->value);
        else
            $list = $list->whereRaw('LOWER(vendor_name) = (?)', [strtolower($req->value)]);
        $list = paginator($list, $req);
        try {
            return responseMsgs(true, "List Fetch Successfully !!!", $list, "055116", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055116", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /* | Get Market Toll Price List
    * | Function - 18
    * | API - 17
    */
    public function calculateTollPrice(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "tollId" => "required|integer",
            "dateUpto" => "required|date|date_format:Y-m-d",
            "dateFrom" => "required|date|date_format:Y-m-d|before_or_equal:$req->dateUpto",
        ]);

        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), [], "055117", "1.0", responseTime(), "POST", $req->deviceId);

        try {
            // Variable Assignments
            $toll = $this->_mToll::find($req->tollId);
            if (collect($toll)->isEmpty())
                throw new Exception("Toll Not Available for this ID");
            $dateFrom = Carbon::parse($req->dateFrom);
            $dateUpto = Carbon::parse($req->dateUpto);
            // Amount Calculation
            $diffInDays = $dateFrom->diffInDays($dateUpto);
            $noOfDays = $diffInDays + 1;
            $payableAmt = $noOfDays * $toll->rate;
            return responseMsgs(true, "Payable Amount - $payableAmt", ['tollAmount' => $payableAmt], "055117", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055117", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get TC Wise Toll Collection Summery
     * | Function - 19
     * | API - 18
     */
    public function getTCWiseTollCollectionSummary(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => $req->fromDate == NULL ? 'nullable|date_format:Y-m-d' : 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            if ($req->fromDate == NULL) {
                $fromDate = date('Y-m-d');
                $toDate = date('Y-m-d');
            } else {
                $fromDate = $req->fromDate;
                $toDate = $req->toDate;
            }
            $mMarTollPayment = new MarTollPayment();
            $list = $mMarTollPayment->paymentList($req->auth['ulb_id'])->whereBetween('payment_date', [$fromDate, $toDate]);                     // Get Payment List
            $list = $list->where('mar_toll_payments.user_id', $req->auth['id']);
            $list = paginator($list, $req);
            $list['totalCollection'] = collect($list['data'])->sum('amount');
            return responseMsgs(true, "Toll Summary Fetch Successfully !!!", $list, "055118", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055118", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get TC Wise Toll Collection Summery
     * | Function - 20
     * | API - 19
     */
    public function getAllTcWiseCollectionReports(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => $req->fromDate == NULL ? 'nullable|date_format:Y-m-d' : 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            if ($req->fromDate == NULL) {
                $fromDate = date('Y-m-d');
                $toDate = date('Y-m-d');
            } else {
                $fromDate = $req->fromDate;
                $toDate = $req->toDate;
            }
            $mMarTollPayment = new MarTollPayment();
            $list = $mMarTollPayment->tcWiseCollection($req->auth['ulb_id'])->whereBetween('payment_date', [$fromDate, $toDate]);          // Get TC Wise Collection Summary
            $list = paginator($list, $req);
            $list['totalCollection'] = collect($list['data'])->sum('collectionamount');
            return responseMsgs(true, "Toll Summary Fetch Successfully !!!", $list, "055119", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "05519", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

     /**
     * | Get Circle Market or Date wise Collection Summery
     * | Function - 21
     * | API - 20
     */
    public function getCircleMarketDateWiseReports(Request $req){
        $validator = Validator::make($req->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => $req->fromDate == NULL ? 'nullable|date_format:Y-m-d' : 'required|date_format:Y-m-d',
            'marketId'=> 'nullable|integer',
            'circleId'=> 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            if ($req->fromDate == NULL) {
                $fromDate = date('Y-m-d');
                $toDate = date('Y-m-d');
            } else {
                $fromDate = $req->fromDate;
                $toDate = $req->toDate;
            }
            $mMarTollPayment = new MarTollPayment();
            $list = $mMarTollPayment->collectionSummary($req->auth['ulb_id'])->whereBetween('payment_date', [$fromDate, $toDate]);
            $list = paginator($list, $req);
            $list['totalCollection'] = collect($list['data'])->sum('amount');
            return responseMsgs(true, "Toll Summary Fetch Successfully !!!", $list, "055119", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055199", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }
}

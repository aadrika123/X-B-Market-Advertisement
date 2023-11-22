<?php

namespace App\Http\Controllers\Rentals;

use App\BLL\Market\ShopPaymentBll;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ShopRequest;
use App\MicroServices\DocumentUpload;
use App\Models\Master\MCircle;
use App\Models\Master\MMarket;
use App\Models\Rentals\MarShopDemand;
use App\Models\Rentals\MarShopLog;
use App\Models\Rentals\MarShopPayment;
use App\Models\Rentals\MarShopRateList;
use App\Models\Rentals\MarShopTpye;
use App\Models\Rentals\MarShopType;
use App\Models\Rentals\MarTollPayment;
use App\Models\Rentals\Shop;
use App\Models\Rentals\ShopConstruction;
use App\Models\Rentals\ShopPayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

use App\Traits\ShopDetailsTraits;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\PDF;
use GuzzleHttp\Client;

class ShopController extends Controller
{
    use ShopDetailsTraits;
    /**
     * | Created On - 14-06-2023 
     * | Created By - Bikash Kumar
     * | Status - Closed (02 Nov 2023)
     */
    private $_mShops;

    protected $_ulbLogoUrl;
    protected $_callbackUrl;

    public function __construct()
    {
        $this->_mShops = new Shop();                                                                // Object Of Shop Model
        $this->_ulbLogoUrl = Config::get('constants.ULB_LOGO_URL');                                 // Logo Url for Reciept
        $this->_callbackUrl = Config::get('constants.CALLBACK_URL');                                // Callback Url for Payment
    }

    /**
     * | Shop Payments
     * | API - 01
     * | Function - 01
     */
    public function shopPayment(Request $req)
    {
        $shopPmtBll = new ShopPaymentBll();
        $validator = Validator::make($req->all(), [
            "shopId" => "required|integer",
            "paymentMode" => 'required|string',
            "toFYear" => 'required|string',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        // Business Logics
        try {
            $shop = $shopPmtBll->shopPayment($req);
            DB::commit();
            $mobile = $shop['mobile'];
            // $mobile="8271522513";
            if ($mobile != NULL && strlen($mobile) == 10) {
                (Whatsapp_Send(
                    $mobile,
                    "market_test_v1",           // Dear *{{name}}*, your payment has been received successfully of Rs *{{amount}}* on *{{date in d-m-Y}}* for *{{shop/Toll Rent}}*. You can download your receipt from *{{recieptLink}}*
                    [
                        "content_type" => "text",
                        [
                            $shop['allottee'],
                            $shop['amount'],
                            $shop['paymentDate'],
                            "Shop Payment",
                            "https://modernulb.com/advertisement/rental-payment-receipt/" . $shop['tranId']
                        ]
                    ]
                ));
                $url="https://modernulb.com/advertisement/rental-payment-receipt/" . $shop['tranId'];
                // $url="https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf";
                // $url="http://192.168.0.128:3035/advertisement/rental-payment-receipt/" . $shop['tranId'];
                $path= "Uploads/shops/payment/";
                $fileUrl=$this->downloadAndSavePDF($path,$url);
                (Whatsapp_Send(
                    $mobile,
                    "file_test",
                    [
                        "content_type" => "pdfOnly",
                        [
                            [
                                "link" => "https://market.modernulb.com/". $path."/".$fileUrl,
                                "filename" =>$fileUrl,
                            ]
                        
                        ]
                    ],
                ));
            }
            return responseMsgs(true, "Payment Done Successfully", ['paymentAmount' => $shop['amount']], "055001", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "055001", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Add Shop Records
     * | API - 02
     * | Function - 02
     */
    public function store(ShopRequest $req)
    {
        try {
            $docUpload = new DocumentUpload;
            $relativePath = Config::get('constants.SHOP_PATH');
            if (isset($req->photo1Path)) {
                $image = $req->file('photo1Path');
                $refImageName = 'Shop-Photo-1' . '-' . $req->allottee;
                $imageName1 = $docUpload->upload($refImageName, $image, $relativePath);
                $imageName1Absolute = $relativePath;
            }
            if (isset($req->photo2Path)) {
                $image = $req->file('photo2Path');
                $refImageName = 'Shop-Photo-2' . '-' . $req->allottee;
                $imageName2 = $docUpload->upload($refImageName, $image, $relativePath);
                $imageName2Absolute = $relativePath;
            }
            $shopNo = $this->shopIdGeneration($req->marketId);
            $metaReqs = [
                'circle_id' => $req->circleId,
                'market_id' => $req->marketId,
                'allottee' => $req->allottee,
                'shop_no' => $shopNo,
                'address' => $req->address,
                'arrear' => $req->arrear,
                'allotted_length' => $req->allottedLength,
                'allotted_breadth' => $req->allottedBreadth,
                'allotted_height' => $req->allottedHeight,
                'area' => $req->allottedLength * $req->allottedBreadth,
                'present_length' => $req->presentLength,
                'present_breadth' => $req->presentBreadth,
                'present_height' => $req->presentHeight,
                'no_of_floors' => $req->noOfFloors,
                'present_occupier' => $req->presentOccupier,
                'trade_license' => $req->tradeLicense,
                'construction' => $req->construction,
                'electricity' => $req->electricity,
                'water' => $req->water,
                'sale_purchase' => $req->salePurchase,
                'contact_no' => $req->contactNo,
                'longitude' => $req->longitude,
                'latitude' => $req->latitude,
                'photo1_path' => $imageName1 ?? "",
                'photo1_path_absolute' => $imageName1Absolute ?? "",
                'photo2_path' => $imageName2 ?? "",
                'photo2_path_absolute' => $imageName2Absolute ?? "",
                'remarks' => $req->remarks,
                'shop_category_id' => $req->shopCategoryId,
                'last_tran_id' => $req->lastTranId,
                'user_id' => $req->auth['id'],
                'ulb_id' => $req->auth['ulb_id'],
                'attoted_upto' => $req->attotedUpto,
                'shop_type' => $req->shopType,
            ];
            if ($req->shopCategoryId == 3)
                $metaReqs = array_merge($metaReqs, ['rate' => $req->rate]);
            else {
                $metaReqs = array_merge($metaReqs, ['rate' => 50000]);
            }
            $this->_mShops->create($metaReqs);
            return responseMsgs(true, "Successfully Saved", [$metaReqs], "055002", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {

            return responseMsgs(false, $e->getMessage(), [], "055002", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Edit shop Records
     * | API - 03
     * | Function - 03
     */
    public function edit(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric',
            'status' => 'nullable|bool'
        ]);
        if ($validator->fails())
            return $validator->errors();
        try {
            $docUpload = new DocumentUpload;
            $relativePath = Config::get('constants.SHOP_PATH');
            if (isset($req->photo1Path)) {
                $image = $req->file('photo1Path');
                $refImageName = 'Shop-Photo-1' . '-' . $req->allottee;
                $imageName1 = $docUpload->upload($refImageName, $image, $relativePath);
                // $absolutePath = $relativePath;
                $imageName1Absolute = $relativePath;
            }
            if (isset($req->photo2Path)) {
                $image = $req->file('photo2Path');
                $refImageName = 'Shop-Photo-2' . '-' . $req->allottee;
                $imageName2 = $docUpload->upload($refImageName, $image, $relativePath);
                // $absolutePath = $relativePath;
                $imageName2Absolute = $relativePath;
            }
            $metaReqs = [
                'circle_id' => $req->circleId,
                'market_id' => $req->marketId,
                'allottee' => $req->allottee,
                'address' => $req->address,
                'rate' => $req->rate,
                'arrear' => $req->arrear,
                'allotted_length' => $req->allottedLength,
                'allotted_breadth' => $req->allottedBreadth,
                'allotted_height' => $req->allottedHeight,
                'area' => $req->area,
                'present_length' => $req->presentLength,
                'present_breadth' => $req->presentBreadth,
                'present_height' => $req->presentHeight,
                'no_of_floors' => $req->noOfFloors,
                'present_occupier' => $req->presentOccupier,
                'trade_license' => $req->tradeLicense,
                'construction' => $req->construction,
                'electricity' => $req->electricity,
                'water' => $req->water,
                'sale_purchase' => $req->salePurchase,
                'contact_no' => $req->contactNo,
                'longitude' => $req->longitude,
                'latitude' => $req->latitude,
                // 'photo1_path' => $imageName1 ?? "",
                // 'photo1_path_absolute' => $imageName1Absolute ?? "",
                // 'photo2_path' => $imageName2 ?? "",
                // 'photo2_path_absolute' => $imageName2Absolute ?? "",
                'remarks' => $req->remarks,
                'last_tran_id' => $req->lastTranId,
                'user_id' => $req->auth['id'],
                'ulb_id' => $req->auth['ulb_id'],
                'alloted_upto' => $req->allotedUpto,
                'shop_type' => $req->shopType,
            ];
            if (isset($req->status)) {                  // In Case of Deactivation or Activation
                $status = $req->status == false ? 0 : 1;
                $metaReqs = array_merge($metaReqs, ['status', $status]);
            }
            if (isset($req->photograph1)) {
                $metaReqs = array_merge($metaReqs, ['photo1_path', $imageName1]);
                $metaReqs = array_merge($metaReqs, ['photo1_path_absolute', $imageName1Absolute]);
            }
            if (isset($req->photograph2)) {
                $metaReqs = array_merge($metaReqs, ['photo2_path', $imageName2]);
                $metaReqs = array_merge($metaReqs, ['photo2_path_absolute', $imageName2Absolute]);
            }
            $Shops = $this->_mShops::findOrFail($req->id);
            $Shops->update($metaReqs);
            // Update Log Data
            $logData = [
                'shop_id' => $req->id,
                'user_id' => $req->auth['id'],
                'change_data' => json_encode($req->all()),
                'date' => Carbon::now()->format('Y-m-d'),
            ];
            MarShopLog::create($logData);
            return responseMsgs(true, "Successfully Updated", [], "055003", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055003", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Shop Details By Id With Demand And Paid Amount
     * | API - 04
     * | Function - 04
     */
    public function show(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $details = $this->_mShops->getShopDetailById($req->id);                                             // Get Shop Details By ID
            if (collect($details)->isEmpty())
                throw new Exception("Shop Does Not Exists");
            // Basic Details
            $basicDetails = $this->generateBasicDetails($details);                                              // Generate Basic Details of Shop
            $shop['shopDetails'] = $basicDetails;
            $mMarShopDemand = new MarShopDemand();
            $demands = $mMarShopDemand->getDemandByShopId($req->id);                                            // Get List of Generated All Demands against SHop
            $total = $demands->pluck('amount')->sum();
            $financialYear = $demands->where('payment_status', '0')->where('amount', '>', '0')->pluck('financial_year');
            $f_y = array();
            foreach ($financialYear as $key => $fy) {
                $f_y[$key]['id'] = $fy;
                $f_y[$key]['financialYear'] = $fy;
            }
            $shop['fYear'] = $f_y;
            $shop['demands'] = $demands;
            $shop['total'] =  round($total, 2);
            $mMarShopPayment = new MarShopPayment(); // DB::enableQueryLog();
            $payments = $mMarShopPayment->getPaidListByShopId($req->id);                                        // Get Paid Demand Against Shop
            $totalPaid = $payments->pluck('amount')->sum();
            // $shop['payments'] = $payments;
            $shop['totalPaid'] =   round($totalPaid, 2);
            $shop['pendingAmount'] =  round(($total - $totalPaid), 2);
            // return([DB::getQueryLog(),$payments]);
            return responseMsgs(true, "", $shop, "055004", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055004", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Activate or De-Activate Shop by Id
     * | API - 05
     * | Function - 05
     */
    public function delete(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|integer',
            'status' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors(), []);
        }
        try {
            $mMarShopDemand = new MarShopDemand();
            $demands = $mMarShopDemand->getDemandByShopId($req->id);                                            // Get List of Generated All Demands against SHop
            $total = $demands->pluck('amount')->sum();
            $mMarShopPayment = new MarShopPayment(); // DB::enableQueryLog();
            $payments = $mMarShopPayment->getPaidListByShopId($req->id);                                        // Get Paid Demand Against Shop
            $totalPaid = $payments->pluck('amount')->sum();
            $pendingAmount =  round(($total - $totalPaid), 2);
            if ($pendingAmount > 0)
                throw new Exception("First Clear All Due Amount !!!");
            if (isset($req->status)) {                                                                          // In Case of Deactivation or Activation
                $status = $req->status == false ? 0 : 1;
                $metaReqs = [
                    'status' => $status
                ];
            }
            if ($req->status == '0') {
                $message = "Shop De-Activated Successfully !!!";
            } else {
                $message = "Shop Activated Successfully !!!";
            }
            $Shops = $this->_mShops::findOrFail($req->id);
            $Shops->update($metaReqs);
            return responseMsgs(true, $message, [], "055005", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055005", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | List Ulb Wise Circle
     * | API - 06
     * | Function - 06
     */
    public function listUlbWiseCircle(Request $req)
    {
        try {
            $mMCircle = new MCircle();
            $list = $mMCircle->getCircleByUlbId($req->auth['ulb_id']);                                      // Get Circle List By ULB ID
            return responseMsgs(true, "Circle List Featch Successfully !!!", $list, "055006", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055006", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Market list Circle wise
     * | API - 07
     * | Function - 07
     */
    public function listCircleWiseMarket(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'circleId' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $mMMarket = new MMarket();
            $list = $mMMarket->getMarketByCircleId($req->circleId);                                                    // List Market List By Circle ID
            return responseMsgs(true, "Market List Featch Successfully !!!", $list, "055007", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055007", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get list Shop 
     * | API - 08
     * | Function - 08
     */
    public function listShop(Request $req)
    {
        try {
            $ulbId = $req->auth['ulb_id'];
            $mShop = new Shop();
            $list = $mShop->getAllShopUlbWise($ulbId);
            if ($req->key)
                $list = searchShopRentalFilter($list, $req);
            $list = paginator($list, $req);
            return responseMsgs(true, "Shop List Fetch Successfully !!!", $list, "055008", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055008", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Shop Collection Summery
     * | API - 09
     * | Function - 09
     */
    public function getShopCollectionSummary(Request $req)
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
            $mShopPayment = new ShopPayment();
            $list = $mShopPayment->paymentList($req->auth['ulb_id'])->whereBetween('payment_date', [$fromDate, $toDate]);   // Get Payment List Between Two Dates
            $list = paginator($list, $req);
            $list['todayCollection'] = $mShopPayment->todayShopCollection($req->auth['ulb_id'], date('Y-m-d'))->get()->sum('amount');
            return responseMsgs(true, "Shop Summary Fetch Successfully !!!", $list, "055009", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055009", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get TC Collection Datewise 
     * | API - 10
     * | Function - 10
     */
    public function getTcCollection(Request $req)
    {
        // return $req;
        $validator = Validator::make($req->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => $req->fromDate == NULL ? 'nullable|date_format:Y-m-d' : 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $authUrl = Config::get('constants.AUTH_URL');
            if ($req->fromDate == NULL) {
                $fromDate = date('Y-m-d');
                $toDate = date('Y-m-d');
            } else {
                $fromDate = $req->fromDate;
                $toDate = $req->toDate;
            }
            $mShopPayment = new ShopPayment();
            $shopPayment = $mShopPayment->paymentListForTcCollection($req->auth['ulb_id'])->whereBetween('payment_date', [$fromDate, $toDate])->get();
            $todayShopPayment = $mShopPayment->paymentListForTcCollection($req->auth['ulb_id'])->where('payment_date', date('Y-m-d'))->sum('amount');
            $mMarTollPayment = new MarTollPayment();
            $tollPayment = $mMarTollPayment->paymentListForTcCollection($req->auth['ulb_id'])->whereBetween('payment_date', [$fromDate, $toDate])->get();
            $todayTollPayment = $mMarTollPayment->paymentListForTcCollection($req->auth['ulb_id'])->where('payment_date', date('Y-m-d'))->sum('amount');
            $totalCollection = collect($shopPayment)->merge($tollPayment);
            $refValues = collect($totalCollection)->pluck('user_id')->unique();
            $ids['ids'] = $refValues;
            $userDetails = Http::withToken($req->token)
                ->post($authUrl . 'api/user-managment/v1/crud/multiple-user/list', $ids);

            //   return  $userDetails = json_decode($userDetails);
            //     $list = collect($refValues)->map(function ($values) use ($totalCollection, $userDetails) {
            //         $ref['totalAmount'] = $totalCollection->where('user_id', $values)->sum('amount');
            //         $ref['userId'] = $values;
            //         $ref['tcName'] = collect($userDetails->data)->where('id', $values)->pluck('name')->first();
            //         return $ref;
            //     });
            //     $list1['list'] = $list->values();
            $list1['todayPayments'] = $todayTollPayment + $todayShopPayment;
            return responseMsgs(true, "TC Collection Fetch Successfully !!!", $list1, "055010", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055010", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | get Shop Master Data
     * | API - 11
     * | Function - 11
     */
    public function shopMaster(Request $req)
    {
        try {
            $mMarShopType = new MarShopType();
            $mMCircle = new MCircle();
            $mShopConstruction = new ShopConstruction();
            $list['shopType'] = $mMarShopType->listShopType();                                                      // Get All Type of Shop
            $list['circleList'] = $mMCircle->getCircleByUlbId($req->auth['ulb_id']);                                // Get Circle / Zone by ULB Id
            $list['listConstruction'] = $mShopConstruction->listConstruction();                                     // Get List of Building Type
            $fYear = FyListdescForShop();                                                                           // Get Financial Year
            $f_y = array();
            foreach ($fYear as $key => $fy) {
                $f_y[$key]['id'] = $fy;
                $f_y[$key]['financialYear'] = $fy;
            }
            $list['fYear'] = $f_y;
            return responseMsgs(true, "Shop Type List !!!", $list, "055011", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055011", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Search Shop For Payment
     * | API - 12
     * | Function - 12
     */
    public function searchShopForPayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopCategoryId' => 'required|integer',
            'circleId' => 'required|integer',
            'marketId' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $mShop = new Shop();
            // $list = $mShop->searchShopForPayment($req->shopCategoryId, $req->circleId, $req->marketId);
            $list = $mShop->searchShopForPayment($req->shopCategoryId, $req->marketId);                                       // Get List Shop FOr Payment
            if ($req->key)
                $list = searchShopRentalFilter($list, $req);
            $list = paginator($list, $req);
            return responseMsgs(true, "Shop List Fetch Successfully !!!",  $list, "055012", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055012", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Calculate Shop rate financial Wise (Given Two Financial Year)
     * | API - 13
     * | Function - 13
     */
    public function calculateShopRateFinancialwise(Request $req)
    {
        $shopPmtBll = new ShopPaymentBll();
        $validator = Validator::make($req->all(), [
            "shopId" => "required|integer",
            "toFYear" => 'required|string',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        // Business Logics
        try {
            $amount = $shopPmtBll->calculateRateFinancialYearWiae($req);                                        // Calculate amount according to Financial Year wise
            return responseMsgs(true, "Amount Fetch Successfully", ['amount' => $amount], "055013", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055013", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Entry Cheque or DD For Payment
     * | API - 14
     * | Function - 14
     */
    public function entryCheckOrDD(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopId' => 'required|integer',
            'bankName' => 'required|string',
            'branchName' => 'required|string',
            'chequeNo' => $req->ddNo == NULL ? 'required|integer' : 'nullable|integer',
            'ddNo' => $req->chequeNo == NULL ? 'required|integer' : 'nullable|integer',
            "toFYear" => 'required|string',
            "paymentMode" => 'required|string',
            "chequeDdDate" => 'required|date_format:Y-m-d|after_or_equal:' . Carbon::now()->subMonth(2)->format('d-m-Y'),
            'photo'  =>   'required|image|mimes:jpg,jpeg,png',
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors()->first(), [], "055014", "1.0", responseTime(), "POST", $req->deviceId);
        }
        try {
            $docUpload = new DocumentUpload;
            $relativePath = Config::get('constants.SHOP_PATH');
            if (isset($req->photo)) {
                $image = $req->file('photo');
                $refImageName = 'Shop-cheque-1' . $req->allottee;
                $imageName1 = $docUpload->upload($refImageName, $image, $relativePath);
                $imageName1Absolute = $relativePath;
            }
            $req->merge(['photo_path' => $imageName1 ?? ""]);
            $req->merge(['photo_path_absolute' => $imageName1Absolute ?? ""]);
            $mMarShopPayment = new MarShopPayment();
            $res = $mMarShopPayment->entryCheckDD($req);                                                            // Store Cheque or DD Details in Shop Payment Table
            $mobile = $res['shopDetails']['mobile'];
            // $mobile = "8271522513";
            if ($mobile != NULL && strlen($mobile) == 10) {
                (Whatsapp_Send(
                    $mobile,
                    "market_test_v1",           // Dear *{{name}}*, your payment has been received successfully of Rs *{{amount}}* on *{{date in d-m-Y}}* for *{{shop/Toll Rent}}*. You can download your receipt from *{{recieptLink}}*
                    [
                        "content_type" => "text",
                        [
                            $res['shopDetails']['allottee'],
                            $res['amount'],
                            Carbon::now()->format('d-m-Y'),
                            "Shop Payment",
                            "https://modernulb.com/advertisement/rental-payment-receipt/" . $res['lastTranId']
                        ]
                    ]
                ));
            }
            return responseMsgs(true, "Cheque or DD Entry Successfully", ['details' => $res['createdPayment']], "055014", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055014", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }


    /**
     * | List cheque or DD For Clearance
     * | API - 15
     * | Function - 15
     */
    public function listEntryCheckorDD(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => $req->fromDate != NULL ? 'required|date_format:Y-m-d|after_or_equal:fromDate' : 'nullable|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors()->first(), [], "055014", "1.0", responseTime(), "POST", $req->deviceId);
        }
        try {
            $mMarShopPayment = new MarShopPayment();
            $data = $mMarShopPayment->listUnclearedCheckDD($req);                                                   // Get List of Cheque or DD
            if ($req->fromDate != NULL) {
                $data = $data->whereBetween('mar_shop_payments.payment_date', [$req->fromDate, $req->toDate]);
            }
            $list = paginator($data, $req);
            return responseMsgs(true, "List Uncleared Check Or DD", $list, "055015", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055015", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Clear or Bounce Cheque or DD (i.e. After Bank Reconsile )
     * | API - 16
     * | Function - 16
     */
    public function clearOrBounceChequeOrDD(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'chequeId' => 'required|integer',
            'status' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'remarks' => $req->status == 3 ? 'required|string' : 'nullable|string',
            'amount' => $req->status == 3 ? 'nullable|numeric' : 'nullable',
            'bounceReason' => $req->status == 3 ? 'required|string' : 'nullable|string',
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $shopPayment = $mMarShopPayment = MarShopPayment::find($req->chequeId);                                    // Get Entry Cheque Details                        
            // $mMarShopPayment->payment_date = Carbon::now()->format('Y-m-d');
            $mMarShopPayment->payment_status = $req->status;
            $mMarShopPayment->bounce_amount = $req->amount;
            $mMarShopPayment->bounce_reason = $req->bounceReason;
            $mMarShopPayment->clear_or_bounce_date = $req->date;
            $mMarShopPayment->save();
            if ($req->status == 3) {
                // if cheque is bounced then demand is again generated
                $UpdateDetails = MarShopDemand::where('shop_id',  $shopPayment->shop_id)                             // Get Data For Again Demand Generate
                    ->where('financial_year', '>=', $shopPayment->paid_from)
                    ->where('financial_year', '<=', $shopPayment->paid_to)
                    ->where('amount', '>', 0)
                    ->orderBy('financial_year', 'ASC')
                    ->get();
                foreach ($UpdateDetails as $updateData) {                                                           // Update Demand Table With Demand Generate 
                    $updateRow = MarShopDemand::find($updateData->id);
                    $updateRow->payment_date = Carbon::now()->format('Y-m-d');
                    $updateRow->payment_status = 0;
                    $updateRow->payment_date = NULL;
                    $updateRow->tran_id = NULL;
                    $updateRow->save();
                }
            }
            if ($req->status == 1) {
                $msg = $shopPayment->pmt_mode . " Cleared Successfully !!!";
                $shop = Shop::find($shopPayment->shop_id);
                $mobile = $shop['contact_no'];
                // $mobile = "8271522513";
                if ($mobile != NULL && strlen($mobile) == 10) {
                    (Whatsapp_Send(
                        $mobile,
                        "market_test_v1",           // Dear *{{name}}*, your payment has been received successfully of Rs *{{amount}}* on *{{date in d-m-Y}}* for *{{shop/Toll Rent}}*. You can download your receipt from *{{recieptLink}}*
                        [
                            "content_type" => "text",
                            [
                                $shop['allottee'],
                                $shopPayment->amount,
                                Carbon::now()->format('d-m-Y'),
                                "Shop Payment",
                                "https://modernulb.com/advertisement/rental-payment-receipt/" . $shopPayment->id
                            ]
                        ]
                    ));
                }
            } else {
                $msg = $shopPayment->pmt_mode . " Has Been Bounced !!!";
                return responseMsgs(true, $msg, '', "055016", "1.0", responseTime(), "POST", $req->deviceId);
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055016", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | List shop Collection between two dates
     * | API - 17
     * | Function - 17
     */
    public function listShopCollection(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopCategoryId' => 'required|integer',
            'marketId' => 'required|integer',
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => 'nullable|date_format:Y-m-d|after_or_equal:fromDate',
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            if (!isset($req->fromDate))
                $fromDate = Carbon::now()->format('Y-m-d');                                                 // if date Is not pass then From Date take current Date
            else
                $fromDate = $req->fromDate;
            if (!isset($req->toDate))
                $toDate = Carbon::now()->format('Y-m-d');                                                  // if date Is not pass then to Date take current Date
            else
                $toDate = $req->toDate;
            $mMarShopPayment = new MarShopPayment();
            $data = $mMarShopPayment->listShopCollection($fromDate, $toDate);                              // Get Shop Payment collection between givrn two dates
            if ($req->shopCategoryId != 0)
                $data = $data->where('t2.shop_category_id', $req->shopCategoryId);
            if ($req->marketId != 0)
                $data = $data->where('t2.market_id', $req->marketId);
            if ($req->auth['user_type'] == 'JSK' || $req->auth['user_type'] == 'TC')
                $data = $data->where('mar_shop_payments.user_id', $req->auth['id']);
            $list = paginator($data, $req);
            $list['collectAmount'] = $data->sum('amount');
            return responseMsgs(true, "Shop Collection List Fetch Succefully !!!", $list, "055017", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055017", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Edit Shop Data For Contact Number
     * | API - 18
     * | Function - 18
     */
    public function editShopData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopId' => 'required|numeric',
            'contactNo' => 'nullable|numeric|digits:10',
            'rentType' => 'nullable|string',
            'remarks' => 'nullable|string',
            'circleId' => 'nullable|integer',                                                 // Circle i.e. Zone
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);

        try {
            $shopDetails = Shop::find($req->shopId);
            $shopDetails->contact_no = $req->contactNo;
            $shopDetails->rent_type = $req->rentType;
            $shopDetails->circle_id = $req->circleId;
            $shopDetails->remarks = $req->remarks;
            $shopDetails->save();
            // Generate Edit Logs
            $logData = [
                'shop_id' => $req->shopId,
                'user_id' => $req->auth['id'],
                'change_data' => json_encode($req->all()),
                'date' => Carbon::now()->format('Y-m-d'),
            ];
            MarShopLog::create($logData);
            return responseMsgs(true, "Update Shop Successfully !!!", '', "055018", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055018", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | DCB Reports of All Shops
     * | API - 19
     * | Function - 19
     */
    public function dcbReports(Request $req)
    {
        try {
            $shopType = MarShopType::select('shop_type', 'id')->where('status', '1')->orderBy('id')->get();
            $mMarShopDemand = new MarShopDemand();
            $mMarShopPayment = new MarShopPayment();
            $mShop = new Shop();
            $total = array();
            foreach ($shopType as $key => $st) {
                $sType = str_replace(" ", "_", $st['shop_type']);
                $total[$sType]['shopCategoryId'] = $st['id'];
                $total[$sType]['totalShop'] = $mShop->totalShop($st['id']);
                $demand = (float)$mMarShopDemand->totalDemand($st['id']);
                $collection = (float)$mMarShopPayment->totalCollectoion($st['id']);
                $total[$sType]['totalDemand'] = number_format($demand, 2);
                $total[$sType]['totalCollection'] = number_format($collection, 2);
                $total[$sType]['totalBalance'] = number_format($demand - $collection, 2);
                $total[$sType]['totalCollectInPercent'] = number_format(($collection / $demand) * 100, 2);

                $total[$sType]['totalDemandGraph'] = $demand;
                $total[$sType]['totalCollectionGraph'] = $mMarShopPayment->totalCollectoion($st['id']);
                $total[$sType]['totalBalanceGraph'] = $demand - $collection;
            }
            return responseMsgs(true, "DCB Reports !!!", $total, "055019", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055019", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Calculate Shop wise DCB
     * | API - 20
     * | Function - 20
     */
    public function shopWiseDcb(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopCategoryId' => 'nullable|integer',
            'marketId' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $mShop = new Shop();
            $shopIds = $mShop->shopwiseDcb();
            if ($req->shopCategoryId) {
                $shopIds =  $shopIds->where('mar_shops.shop_category_id', $req->shopCategoryId);
            }
            if ($req->marketId) {
                $shopIds =  $shopIds->where('mar_shops.market_id', $req->marketId);
            }
            $shopIds =  $shopIds->orderBy('mar_shops.id', 'ASC')->orderBy('mar_shops.shop_category_id', 'ASC');

            $mMarShopDemand = new MarShopDemand();
            $mMarShopPayment = new MarShopPayment();
            $marketDemand = collect();
            $marketCollection = collect();
            $totalMarketDCB = collect($shopIds->get())->map(function ($shop) use ($mMarShopDemand, $mMarShopPayment, $marketDemand, $marketCollection) {
                $marketDemand->push($mMarShopDemand->shopDemand($shop->id));
                $marketCollection->push($mMarShopPayment->shopCollectoion($shop->id));
            });
            $totalDemand = $marketDemand->sum() > 0 ? $marketDemand->sum() : 0;
            $totalCollection = $marketCollection->sum() > 0 ? $marketCollection->sum() : 0;
            DB::enableQueryLog();
            $list = paginator($shopIds, $req); #return(DB::getQueryLog());
            $shops = array();
            $shops = collect($list['data'])->map(function ($val) use ($mMarShopDemand, $mMarShopPayment) {
                $val->totalDemand = $mMarShopDemand->shopDemand($val->id);
                $val->totalCollection = $mMarShopPayment->shopCollectoion($val->id);
                $val->balance =  $val->totalDemand - $val->totalCollection;
                return $val;
            });
            $list["data"] = $shops->toArray();
            $list['totalMarketDemand'] = number_format($totalDemand, 2);
            $list['totalMarketCollection'] = number_format($totalCollection, 2);
            $list['totalMarketBalance'] = number_format($totalDemand - $totalCollection);
            return responseMsgs(true, "DCB Reports !!!", $list, "055020", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055020", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Generate Refferal Url For Online Payment 
     * | API - 21
     * | Function - 21
     */
    public function generateReferalUrlForPayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "shopId" => "required|integer",
            "paymentMode" => 'required|string',
            "toFYear" => 'required|string',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $amount = DB::table('mar_shop_demands')                                                       // Calculate Amount For Selected Financial Year
                ->where('shop_id', $req->shopId)
                ->where('payment_status', 0)
                ->where('financial_year', '<=', $req->toFYear)
                ->orderBy('financial_year', 'ASC')
                ->sum('amount');
            if ($amount < 1)
                throw new Exception("No Any Due Amount !!!");
            $shopDetails = DB::table('mar_shops')->select('*')->where('id', $req->shopId)->first();      // Get Shop Details By Shop Id
            $financialYear = DB::table('mar_shop_demands')                                               // Get First Financial Year For Payment
                ->where('shop_id', $req->shopId)
                ->where('payment_status', 0)
                ->where('financial_year', '<=', $req->toFYear)
                ->where('amount', '>', '0')
                ->orderBy('financial_year', 'ASC')
                ->first('financial_year');
            $refReq = new Request([                                                                     // Make Payload For Online Payment
                'amount' => $amount,
                'id' => $req->shopId,
                'moduleId' => 5,                                                                        // Market- Advertisement Module Id
                'auth' => $req->auth,
                'callbackUrl' =>  $this->_callbackUrl . 'advertisement/shop-fullDetail-payment/' . $req->shopId,
                'paymentOf' => 1,                                                                        // 1 - for shop, 2 - For Toll                                                                         // After Payment Redirect Url
            ]);
            DB::beginTransaction();
            $paymentUrl = Config::get('constants.PAYMENT_URL');                                         // Get Payment Url From .env via constant page
            $refResponse = Http::withHeaders([                                                          // HTTP Call For generate referal Url
                "api-key" => "eff41ef6-d430-4887-aa55-9fcf46c72c99"
            ])
                ->withToken($req->token)
                ->post($paymentUrl . 'api/payment/v1/get-referal-url', $refReq);
            $data = json_decode($refResponse);
            if ($data->status == false)
                throw new Exception("Payment Referal Url Not Generate");
            // Insert Payment Details in Shop Payment
            $paymentReqs = [
                "req_ref_no" => $data->message->req_ref_no,
                'shop_id' => $req->shopId,
                'amount' => $amount,
                'paid_from' => $financialYear->financial_year,
                'paid_to' => $req->toFYear,
                'payment_date' => Carbon::now(),
                'payment_status' => '0',
                'user_id' => $req->auth['id'] ?? 0,
                'ulb_id' => $shopDetails->ulb_id,
                'remarks' => $req->remarks,
                'pmt_mode' => $req->paymentMode,
                'shop_category_id' => $shopDetails->shop_category_id,
                'referal_url' => $data->data->encryptUrl,
                'transaction_id' => time() . $shopDetails->ulb_id . $req->shopId,                       // Transaction id is a combination of time funcation of PHP and ULB ID and Shop ID
            ];
            MarShopPayment::create($paymentReqs);                                                       // Add Transaction Details in Market Shop Payment Table
            DB::commit();
            return responseMsgs(true, "Proceed For Payment !!!", ['paymentUrl' => $data->data->encryptUrl], "055021", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "055021", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Shop Payment Reciept By Demand ID 
     * | API - 22
     * | Function - 22
     */
    public function shopPaymentReciept($tranId, Request $req)
    {
        try {
            $data = MarShopPayment::select('mar_shop_payments.*', 'users.name as reciever_name')
                ->join('users', 'users.id', 'mar_shop_payments.user_id')
                ->where('mar_shop_payments.id', $tranId)
                ->first();
            if (!$data)
                throw new Exception("Transaction Id Not Valid !!!");
            $shopDetails = $this->_mShops->getShopDetailById($data->shop_id);                                               // Get Shop Details By Shop Id
            $ulbDetails = DB::table('ulb_masters')->where('id', $shopDetails->ulb_id)->first();
            $reciept = array();
            $reciept['shopNo'] = $shopDetails->shop_no;
            $reciept['paidFrom'] = $data->paid_from;
            $reciept['paidTo'] = $data->paid_to;
            $reciept['amount'] = $data->amount;
            $reciept['paymentDate'] =  Carbon::createFromFormat('Y-m-d', $data->payment_date)->format('d-m-Y');
            $reciept['paymentMode'] = $data->pmt_mode;
            $reciept['transactionNo'] = $data->transaction_id;
            $reciept['allottee'] = $shopDetails->allottee;
            $reciept['market'] = $shopDetails->market_name;
            $reciept['shopType'] = $shopDetails->shop_type;
            $reciept['ulbName'] = $ulbDetails->ulb_name;
            $reciept['tollFreeNo'] = $ulbDetails->toll_free_no;
            $reciept['website'] = $ulbDetails->current_website;
            $reciept['ulbLogo'] =  $this->_ulbLogoUrl . $ulbDetails->logo;
            $reciept['recieverName'] =  $data->reciever_name;
            $reciept['paymentStatus'] = $data->payment_status == 1 ? "Success" : ($data->payment_status == 2 ? "Payment Made By " . strtolower($data->pmt_mode) . " are considered provisional until they are successfully cleared." : ($data->payment_status == 3 ? "Cheque/DD Bounce" : "No Any Payment"));
            $reciept['amountInWords'] = getIndianCurrency($data->amount) . "Only /-";                                               // Convert digits to words 

            // If Payment By Cheque then Cheque Details is Added Here
            $reciept['chequeDetails'] = array();
            if (strtoupper($data->pmt_mode) == 'CHEQUE') {
                $reciept['chequeDetails']['cheque_date'] = Carbon::createFromFormat('Y-m-d', $data->cheque_date)->format('d-m-Y');;
                $reciept['chequeDetails']['cheque_no'] = $data->cheque_no;
                $reciept['chequeDetails']['bank_name'] = $data->bank_name;
                $reciept['chequeDetails']['branch_name'] = $data->branch_name;
            }
            // If Payment By DD then DD Details is Added Here
            $reciept['ddDetails'] = array();
            if (strtoupper($data->pmt_mode) == 'DD') {
                $reciept['ddDetails']['cheque_date'] = Carbon::createFromFormat('Y-m-d', $data->cheque_date)->format('d-m-Y');;
                $reciept['ddDetails']['dd_no'] = $data->dd_no;
                $reciept['ddDetails']['bank_name'] = $data->bank_name;
                $reciept['ddDetails']['branch_name'] = $data->branch_name;
            }
            return responseMsgs(true, "Shop Reciept Fetch Successfully !!!", $reciept, "055022", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055022", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Update webhook data when online payment is success 
     * | API - 23
     * | Function - 23
     */
    public function updateWebhookData(Request $req)
    {
        try {
            DB::beginTransaction();
            $data = $req->all();
            $reqRefNo = $req->reqRefNo;
            if ($req->Status == 'SUCCESS' || $req->ResponseCode == 'E000') {
                $mMarShopPayment = new MarShopPayment();
                $paymentReqsData = $mMarShopPayment->findByReqRefNo($reqRefNo);
                $updReqs = [
                    'res_ref_no' => $req->TrnId,
                    'payment_status' => 1,
                    'payment_details' => json_encode($req->all()),
                ];
                $paymentReqsData->update($updReqs);                 // Payment Table Updation after payment is done.
                $UpdateDetails = MarShopDemand::where('shop_id',  $paymentReqsData->shop_id)
                    ->where('financial_year', '>=', $paymentReqsData->paid_from)
                    ->where('financial_year', '<=',  $paymentReqsData->paid_to)
                    ->where('amount', '>', 0)
                    ->orderBy('financial_year', 'ASC')
                    ->get();
                foreach ($UpdateDetails as $updateData) {
                    $updateRow = MarShopDemand::find($updateData->id);
                    $updateRow->payment_date = Carbon::now()->format('Y-m-d');
                    $updateRow->payment_status = 1;
                    $updateRow->tran_id = $paymentReqsData->id;
                    $updateRow->save();
                }
                $shop = $mshop = Shop::find($paymentReqsData->shop_id);
                $lastTranId = $mshop->last_tran_id = $paymentReqsData->id;
                $mshop->save();
            }
            // ❗❗ Pending for Module Specific Table Updation ❗❗
            DB::commit();
            $amount = MarShopPayment::select('amount')->where('id', $lastTranId)->first()->amount;
            $mobile = $shop['mobile'];
            // $mobile = "8271522513";
            if ($mobile != NULL && strlen($mobile) == 10) {
                (Whatsapp_Send(
                    $mobile,
                    "market_test_v1",           // Dear *{{name}}*, your payment has been received successfully of Rs *{{amount}}* on *{{date in d-m-Y}}* for *{{shop/Toll Rent}}*. You can download your receipt from *{{recieptLink}}*
                    [
                        "content_type" => "text",
                        [
                            $shop['allottee'],
                            $amount,
                            Carbon::now()->format('d-m-Y'),
                            "Shop Payment",
                            "https://modernulb.com/advertisement/rental-payment-receipt/" . $lastTranId
                        ]
                    ]
                ));
            }
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * | List Un-Verified cash Payment
     * | API - 24
     * | Function - 24
     */
    public function listUnverifiedCashPayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => $req->fromDate != NULL ? 'required|date_format:Y-m-d|after_or_equal:fromDate' : 'nullable|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors()->first(), [], "055024", "1.0", responseTime(), "POST", $req->deviceId);
        }
        try {
            $mMarShopPayment = new MarShopPayment();
            $data = $mMarShopPayment->listUnverifiedCashPayment($req);
            if ($req->fromDate != NULL) {
                $data = $data->whereBetween('mar_shop_payments.payment_date', [$req->fromDate, $req->toDate]);
            }
            $list = paginator($data, $req);
            return responseMsgs(true, "List Uncleared Cash Payment", $list, "055024", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055024", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Verified Payment one or more than one
     * | API - 25
     * | Function - 25
     */
    public function verifiedCashPayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'ids' => 'required|array',
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors()->first(), [], "055025", "1.0", responseTime(), "POST", $req->deviceId);
        }
        try {
            MarShopPayment::whereIn('id', $req->ids)->update(['is_verified' => '1']);
            return responseMsgs(true, "Payment Verified Successfully !!!",  '', "055025", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055025", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | List Cash Verification userwise
     * | API - 26
     * | Function - 26
     */
    public function listCashVerification(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'date' => 'nullable|date_format:Y-m-d',
            'reportType' => 'nullable|integer|in:0,1',        // 0 - Not Verified, 1 - Verified
            'shopType' => 'nullable|integer|in:1,2,3',        // 1 - BOT Shop, 2 - City Shop, 3 - GP (Gram Panchyat Shop) Shop
            'market' => 'nullable|integer',
            'circle' => 'nullable|integer',                    // Circle i.e. Zone
            'userId' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors()->first(), [], "055026", "1.0", responseTime(), "POST", $req->deviceId);
        }
        try {
            $mMarShopPayment = new MarShopPayment();
            $data = $mMarShopPayment->getListOfPayment();
            if ($req->date != NULL)
                $data = $data->where("mar_shop_payments.payment_date", $req->date);
            if ($req->reportType != NULL)
                $data = $data->where("mar_shop_payments.is_verified", $req->reportType);
            if ($req->shopType != NULL)
                $data = $data->where("t1.shop_category_id", $req->shopType);
            if ($req->market != NULL)
                $data = $data->where("t1.market_id", $req->market);
            if ($req->circle != NULL)
                $data = $data->where("t1.circle_id", $req->circle);
            if ($req->userId != NULL)
                $data = $data->where("user.id", $req->userId);
            $data = $data->groupBy('mar_shop_payments.user_id', 'user.name', 'user.mobile', 'circle_id', 'market_id', 't1.shop_category_id');
            $list = paginator($data, $req);
            return responseMsgs(true, "List of Cash Verification", $list, "055026", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055026", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | List Cash Verification Details by TC or Userwise
     * | API - 27
     * | Function - 27
     */
    public function listDetailCashVerification(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'date' => 'required|date_format:Y-m-d',
            'reportType' => 'nullable|integer|in:0,1',          // 0 - Not Verified, 1 - Verified
            'shopType' => 'nullable|integer|in:1,2,3',          // 1 - BOT Shop, 2 - City Shop, 3 - GP (Gram Panchyat Shop) Shop
            'market' => 'nullable|integer',
            'circle' => 'nullable|integer',                     // Circle i.e. Zone
            'userId' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors()->first(), [], "055027", "1.0", responseTime(), "POST", $req->deviceId);
        }
        try {
            $mMarShopPayment = new MarShopPayment();
            $data = $mMarShopPayment->getListOfPaymentDetails();
            if ($req->date != NULL)
                $data = $data->where("mar_shop_payments.payment_date", $req->date);
            if ($req->reportType != NULL)
                $data = $data->where("mar_shop_payments.is_verified", $req->reportType);
            if ($req->shopType != NULL)
                $data = $data->where("t1.shop_category_id", $req->shopType);
            if ($req->market != NULL)
                $data = $data->where("t1.market_id", $req->market);
            if ($req->circle != NULL)
                $data = $data->where("t1.circle_id", $req->circle);
            if ($req->userId != NULL)
                $data = $data->where("user.id", $req->userId);
            $list = $data->get();
            $cash = $cheque = $dd = 0;
            foreach ($list as $record) {
                if ($record->payment_mode == 'CASH') {
                    $cash += $record->amount;                                                       // Add Cash Amount in cash Variable
                }
                if ($record->payment_mode == 'CHEQUE') {
                    $cheque += $record->amount;                                                     // Add Cheque Amount in cheque Variable
                }
                if ($record->payment_mode == 'DD') {
                    $dd += $record->amount;                                                         // Add DD Amount in DD Variable
                }
            }
            $f_data['data'] = $list;
            $f_data['userDetails']['collector_name'] = $list[0]->collector_name;
            $f_data['userDetails']['total_amount'] = $data->sum('amount');
            $f_data['userDetails']['transactionDate'] = Carbon::createFromFormat('Y-m-d', $req->date)->format('d-m-Y');
            $f_data['userDetails']['no_of_transaction'] = count($list);
            $f_data['userDetails']['cash'] = $cash;
            $f_data['userDetails']['cheque'] = $cheque;
            $f_data['userDetails']['dd'] = $dd;
            return responseMsgs(true, "List of Cash Verification", $f_data, "055027", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055027", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Update Cheque and DD Details By Accountant
     * | API - 28
     * | Function - 28
     */
    public function updateChequeDeails(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|integer',
            'chequeNo' => 'nullable|integer',
            'ddNo' => 'nullable|integer',
            'bankName' => 'required|string',
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors()->first(), [], "055028", "1.0", responseTime(), "POST", $req->deviceId);
        }
        try {
            $paymentDetails = MarShopPayment::find($req->id);
            $paymentDetails->bank_name = $req->bankName;
            if (!$paymentDetails)
                throw new Exception("Payment Details Not Found !!!");
            if ($paymentDetails->pmt_mode == 'CHEQUE') {
                $paymentDetails->cheque_no = $req->chequeNo;
            }
            if ($paymentDetails->pmt_mode == 'DD') {
                $paymentDetails->dd_no = $req->ddNo;
            }
            $paymentDetails->save();
            return responseMsgs(true, "Details Update Successfully !!!", '', "055028", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055028", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Shop Details For Edit
     * | API - 29
     * | Function - 29
     */
    public function shopDetailsForEdit(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $shopdetails = $this->_mShops->getShopDetailById($req->id);                                             // Get Shop Details By ID
            if (collect($shopdetails)->isEmpty())
                throw new Exception("Shop Does Not Exists");
            return responseMsgs(true, "", $shopdetails, "055029", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055029", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Generate Demand Reciept Details Before Payment
     * | API - 30
     * | Function - 30
     */
    public function generateShopDemand(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopId' => 'required|integer',
            'financialYear' => 'required|string',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $mMarShopDemand = new MarShopDemand();
            $shopDemand = $mMarShopDemand->payBeforeDemand($req->shopId, $req->financialYear);                            // Demand Details Before Payment 
            $demands['shopDemand'] = $shopDemand;
            $demands['totalAmount'] = round($shopDemand->pluck('amount')->sum());
            if ($demands['totalAmount'] > 0)
                $demands['amountinWords'] = getIndianCurrency($demands['totalAmount']) . "Only /-";
            $shopDetails = $this->_mShops->getShopDetailById($req->shopId);                                               // Get Shop Details By Shop Id
            $ulbDetails = DB::table('ulb_masters')->where('id', $shopDetails->ulb_id)->first();
            $demands['shopNo'] = $shopDetails->shop_no;
            $demands['allottee'] = $shopDetails->allottee;
            $demands['market'] = $shopDetails->market_name;
            $demands['shopType'] = $shopDetails->shop_type;
            $demands['ulbName'] = $ulbDetails->ulb_name;
            $demands['tollFreeNo'] = $ulbDetails->toll_free_no;
            $demands['website'] = $ulbDetails->current_website;
            $demands['ulbLogo'] =  $this->_ulbLogoUrl . $ulbDetails->logo;
            $demands['rentType'] =  $shopDetails->rent_type;
            return responseMsgs(true, "", $demands, "055030", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055030", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Shop Collection TC Wise
     * | API - 31
     * | Function - 31
     */
    public function getShopCollectionTcWise(Request $req)
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
            $mMarShopPayment = new MarShopPayment();
            $list = $mMarShopPayment->getListOfPayment()->whereBetween('payment_date', [$fromDate, $toDate]);                     // Get Payment List
            $list = $list->groupBy('mar_shop_payments.user_id', 'user.name', 'circle_id', 'user.mobile');
            $list = paginator($list, $req);
            $list['totalCollection'] = collect($list['data'])->sum('amount');
            return responseMsgs(true, "Shop Collection Summary Fetch Successfully !!!", $list, "055131", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055131", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Shop Collection by TC ID
     * | API - 32
     * | Function - 32
     */
    public function getShopCollectionByTcId(Request $req)
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
            $mMarShopPayment = new MarShopPayment();
            $list = $mMarShopPayment->paymentList($req->auth['ulb_id'])->whereBetween('payment_date', [$fromDate, $toDate]);                     // Get Payment List
            $list = $list->where('mar_shop_payments.user_id', $req->auth['id']);
            $list = paginator($list, $req);
            $list['totalCollection'] = collect($list['data'])->sum('amount');
            return responseMsgs(true, "Shop Summary Fetch Successfully !!!", $list, "055132", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055132", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Shop Payment Reciept By Demand ID 
     * | API - 33
     * | Function - 33
     */
    public function shopPaymentRecieptBluetoothPrint(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'tranId' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $data = MarShopPayment::select('mar_shop_payments.*', 'users.name as receiver_name', 'users.mobile as receiver_mobile')
                ->join('users', 'users.id', 'mar_shop_payments.user_id')
                ->where('mar_shop_payments.id', $req->tranId)
                ->first();
            if (!$data)
                throw new Exception("Transaction Id Not Valid !!!");
            $shopDetails = $this->_mShops->getShopDetailById($data->shop_id);                                               // Get Shop Details By Shop Id
            $ulbDetails = DB::table('ulb_masters')->where('id', $shopDetails->ulb_id)->first();
            $reciept = array();
            $reciept['shopNo'] = $shopDetails->shop_no;
            $reciept['paidFrom'] = $data->paid_from;
            $reciept['paidTo'] = $data->paid_to;
            $reciept['amount'] = $data->amount;
            $reciept['paymentDate'] =  Carbon::createFromFormat('Y-m-d', $data->payment_date)->format('d-m-Y');
            $reciept['paymentMode'] = $data->pmt_mode;
            $reciept['transactionNo'] = $data->transaction_id;
            $reciept['allottee'] = $shopDetails->allottee;
            $reciept['market'] = $shopDetails->market_name;
            $reciept['shopType'] = $shopDetails->shop_type;
            $reciept['ulbName'] = $ulbDetails->ulb_name;
            $reciept['tollFreeNo'] = $ulbDetails->toll_free_no;
            $reciept['website'] = $ulbDetails->current_website;
            // $reciept['ulbLogo'] =  $this->_ulbLogoUrl . $ulbDetails->logo;
            $reciept['ulbLogo'] =  $this->_ulbLogoUrl . "Uploads/Icon/akolall.png";
            $reciept['receiverName'] =  $data->receiver_name;
            $reciept['receiverMobile'] =  $data->receiver_mobile;
            $reciept['paymentStatus'] = $data->payment_status == 1 ? "Success" : ($data->payment_status == 2 ? "Payment Made By " . strtolower($data->pmt_mode) . " are considered provisional until they are successfully cleared." : ($data->payment_status == 3 ? "Cheque Bounse" : "No Any Payment"));
            $reciept['amountInWords'] = getIndianCurrency($data->amount) . "Only /-";

            // If Payment By Cheque then Cheque Details is Added Here
            $reciept['chequeDetails'] = array();
            if (strtoupper($data->pmt_mode) == 'CHEQUE') {
                $reciept['chequeDetails']['cheque_date'] = Carbon::createFromFormat('Y-m-d', $data->cheque_date)->format('d-m-Y');;
                $reciept['chequeDetails']['cheque_no'] = $data->cheque_no;
                $reciept['chequeDetails']['bank_name'] = $data->bank_name;
                $reciept['chequeDetails']['branch_name'] = $data->branch_name;
            }
            // If Payment By DD then DD Details is Added Here
            $reciept['ddDetails'] = array();
            if (strtoupper($data->pmt_mode) == 'DD') {
                $reciept['ddDetails']['cheque_date'] = Carbon::createFromFormat('Y-m-d', $data->cheque_date)->format('d-m-Y');;
                $reciept['ddDetails']['dd_no'] = $data->dd_no;
                $reciept['ddDetails']['bank_name'] = $data->bank_name;
                $reciept['ddDetails']['branch_name'] = $data->branch_name;
            }
            return responseMsgs(true, "Shop Reciept Fetch Successfully !!!", $reciept, "055033", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055033", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }
    /**
     * | Get Shop List By Contact No 
     * | API - 33
     * | Function - 33
     */
    public function searchShopByMobileNo(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'mobileNo' => 'required|digits:10',
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $mshop = new Shop();
            $listShop = $mshop->searchShopByContactNo($req->mobileNo)->get();
            // $list = paginator($listShop, $req);
            return responseMsgs(true, "Shop List Fetch Successfully !!!", $listShop, "055034", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055034", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Calculate Shop Rate At The Time of Shop Entry
     * | Function - 34
     */
    public function calculateShopRate($shopCategoryId, $area, $financialYear)
    {
        $mMarShopRateList = new MarShopRateList();
        $base_rate = $mMarShopRateList->getShopRate($shopCategoryId, $financialYear);
        if ($shopCategoryId == 1) {
            $base_rate = 5;                                     // Get Base rate of BOT Shop financial yearwise
            return ($base_rate * $area * 12);                   // BOT Amount Calculation
        } else {
            $base_rate = 5;                                     // Get Base rate of City shop financial yearwise
            return ($base_rate * $area * 12);                   // BOT Amount Calculation
        }
    }

    /**
     * | ID Generation For Shop
     * | Function - 35
     */
    public function shopIdGeneration($marketId)
    {
        $idDetails = DB::table('m_market')->select('shop_counter', 'market_name')->where('id', $marketId)->first();
        $market = strtoupper(substr($idDetails->market_name, 0, 3));
        $counter = $idDetails->shop_counter + 1;
        DB::table('m_market')->where('id', $marketId)->update(['shop_counter' => $counter]);
        return $id = "SHOP-" . $market . "-" . (1000 + $idDetails->shop_counter);                           // SHOP- ,three character of market name, 1000 + current counter 
    }


    /**
     * | this is for test whatsappp mesaging
     */
    public function sendSms(Request $request)
    {
        try {
            // return $whatsapp2 = (Whatsapp_Send(
            //     6206998554,
            //     "test_file_v3",
            //     [
            //         "content_type" => "pdf",
            //         [
            //             [
            //                 "link" => "https://egov.modernulb.com/Uploads/Icon/Water%20_%20Akola%20Municipal%20Corportation%202.pdf",
            //                 "filename" => "TEST_PDF" . ".pdf"
            //             ],
            //         ],
            //         "text" => [
            //             "17",
            //             "CON-100345",
            //             "https://modernulb.com/water/waterViewDemand/28"
            //         ]
            //     ]
            // ));

            // $whatsapp2 = (Whatsapp_Send(
            //     8271522513,
            //     "test_file_v4",
            //     [
            //         "content_type" => "text",
            //         [
            //             "bikash jee",
            //             "2005-09-01",
            //             "2005-09-01",
            //             "30",
            //             "5 parameter"
            //         ]
            //     ]
            // ));
            $data["data"] = ["afsdf", "sdlfjksld", "dfksdfjk"];
            # Watsapp pdf sending
            $filename = "1-2-" . time() . '.' . 'pdf';
            $url = "Uploads/shop/payment/" . $filename;
            $customPaper = array(0, 0, 720, 1440);
            $pdf = PDF::loadView('payment-receipt',  ['returnValues' => $data])->setPaper($customPaper, 'portrait');
            $file = $pdf->download($filename . '.' . 'pdf');
            $pdf = Storage::put('public' . '/' . $url, $file);
            (Whatsapp_Send(
                8271522513,
                "file_test",
                [
                    "content_type" => "pdfOnly",
                    [
                        [
                            // "link" => config('app.url') . "/getImageLink?path=" . $url,
                            "link" => "https://egov.modernulb.com/Uploads/Icon/Water%20_%20Akola%20Municipal%20Corportation%202.pdf",
                            "filename" => $filename . ".pdf"
                        ]

                    ]
                ],
            ));


            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // public function downloadAndSavePDF($url, $storagePath)
    // {
    //     // Get the file content from the URL
    //     $fileContent = file_get_contents($url);

    //     // Check if the file content was retrieved successfully
    //     if ($fileContent !== false) {
    //         // Store the file content in the storage path
    //         Storage::put('public' . '/' .$storagePath, $fileContent);

    //         // You can also use the Storage facade to generate a URL for the stored file
    //         $fileUrl = Storage::url($storagePath);

    //         // Optionally, you can return the URL or perform any other actions
    //         return $fileUrl;
    //     } else {
    //         // Handle the case where the file content couldn't be retrieved
    //         return false;
    //     }
    // }
    public function downloadAndSavePdf($path,$url)
    {
        // Get the URL from the request or replace it with your desired URL
        // $url =  "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf";

        // Use Guzzle to make the HTTP request
        $client = new Client();
        $response = $client->get($url);

        // Get the content of the response
        $pdfContent = $response->getBody()->getContents();

        // Generate a unique filename for the saved PDF
        $filename = 'downloaded_pdf_' . time() . '.pdf';

        // Save the PDF to the storage disk (default is 'public')
        Storage::put('public' . '/' .$path.'/'.$filename, $pdfContent);

        // Optionally, you can return a response or redirect
        return  $filename;
    }
}

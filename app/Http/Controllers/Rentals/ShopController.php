<?php

namespace App\Http\Controllers\Rentals;

use App\BLL\Market\ShopPaymentBll;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ShopRequest;
use App\MicroServices\DocumentUpload;
use App\Models\Master\MCircle;
use App\Models\Master\MMarket;
use App\Models\Rentals\MarShopDemand;
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

class ShopController extends Controller
{
    use ShopDetailsTraits;
    /**
     * | Created On-14-06-2023 
     * | Created By - Bikash Kumar
     */
    private $_mShops;
    private $_tranId;

    protected $_ulbLogoUrl;

    public function __construct()
    {
        $this->_mShops = new Shop();
        $this->_ulbLogoUrl = Config::get('constants.ULB_LOGO_URL');
    }

    /**
     * | Shop Payments
     */
    public function shopPaymentold(Request $req)
    {
        $shopPmtBll = new ShopPaymentBll();
        $validator = Validator::make($req->all(), [
            "shopId" => "required|integer",
            "paymentTo" => "required|date|date_format:Y-m-d",
            // "amount" => 'required|numeric'
        ]);
        $validator->sometimes("paymentFrom", "required|date|date_format:Y-m-d|before_or_equal:$req->paymentTo", function ($input) use ($shopPmtBll) {
            $shopPmtBll->_shopDetails = $this->_mShops::findOrFail($input->shopId);
            $shopPmtBll->_tranId = $shopPmtBll->_shopDetails->last_tran_id;
            return !isset($shopPmtBll->_tranId);
        });

        if ($validator->fails())
            return $validator->errors();
        // Business Logics
        try {
            $amount = $shopPmtBll->shopPayment($req);
            DB::commit();
            return responseMsgs(true, "Payment Done Successfully", ['paymentAmount' => $amount], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Shop Payments
     */
    public function shopPayment(Request $req)
    {
        $shopPmtBll = new ShopPaymentBll();
        $validator = Validator::make($req->all(), [
            "shopId" => "required|integer",
            // "amount" => 'required|numeric',
            "paymentMode" => 'required|string',
            "fromFYear" => 'required|string',
            "toFYear" => 'required|string',
        ]);
        // $validator->sometimes("paymentFrom", "required|date|date_format:Y-m-d|before_or_equal:$req->paymentTo", function ($input) use ($shopPmtBll) {
        //     $shopPmtBll->_shopDetails = $this->_mShops::findOrFail($input->shopId);
        //     $shopPmtBll->_tranId = $shopPmtBll->_shopDetails->last_tran_id;
        //     return !isset($shopPmtBll->_tranId);
        // });
        // if ($validator->fails())
        //     return $validator->errors();
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        // Business Logics
        try {
            $amount = $shopPmtBll->shopPayment($req);
            DB::commit();
            return responseMsgs(true, "Payment Done Successfully", ['paymentAmount' => $amount], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }
    /**
     * | Get Shop Payment Reciept By Demand ID
     */
    public function shopPaymentReciept(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "tranId" => "required|integer",
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $data = MarShopPayment::find($req->tranId);
            if (!$data)
                throw new Exception("Transaction Id Not Valid !!!");
            $shopDetails = $this->_mShops->getShopDetailById($data->shop_id);
            $ulbDetails = DB::table('ulb_masters')->where('id', $shopDetails->ulb_id)->first();
            $reciept = array();
            $reciept['paidFrom'] = $data->paid_from;
            $reciept['paidTo'] = $data->paid_to;
            $reciept['amount'] = $data->amount;
            $reciept['paymentDate'] =  Carbon::createFromFormat('Y-m-d', $data->payment_date)->format('d-m-Y');;
            $reciept['paymentMode'] = $data->pmt_mode;
            $reciept['transactionNo'] = $data->transaction_id;
            $reciept['allottee'] = $shopDetails->allottee;
            $reciept['market'] = $shopDetails->market_name;
            $reciept['shopType'] = $shopDetails->shop_type;
            $reciept['ulbName'] = $ulbDetails->ulb_name;
            $reciept['tollFreeNo'] = $ulbDetails->toll_free_no;
            $reciept['website'] = $ulbDetails->current_website;
            $reciept['ulbLogo'] =  $this->_ulbLogoUrl . $ulbDetails->logo;
            $reciept['amountInWords'] = getIndianCurrency($data->amount) . " Only /-";
            return responseMsgs(true, "Shop Reciept Fetch Successfully !!!", $reciept, 055001, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Add Shop Records
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
            $shopNo = $this->shopIdGeneration($req->marketId);
            $metaReqs = [
                'circle_id' => $req->circleId,
                'market_id' => $req->marketId,
                'allottee' => $req->allottee,
                'shop_no' => $shopNo,
                'address' => $req->address,
                // 'rate' => $req->rate,
                'arrear' => $req->arrear,
                'allotted_length' => $req->allottedLength,
                'allotted_breadth' => $req->allottedBreadth,
                'allotted_height' => $req->allottedHeight,
                'area' => $req->allottedLength * $req->allottedBreadth,
                // 'area' => $req->area,
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
                'ulb_id' => $req->auth['ulb_id']
            ];
            // return $metaReqs;
            if ($req->shopCategoryId == 3)
                $metaReqs = array_merge($metaReqs, ['rate' => $req->rate]);
            else {
                $metaReqs = array_merge($metaReqs, ['rate' => 50000]);
                // $area=$req->allottedLength * $req->allottedBreadth;
                // $financialYear=getFinancialYear(Carbon::now()->format('Y-m-d'));
                // $rate=$this->calculateShopRate($req->shopCategoryId,$area,$financialYear);
            }

            $this->_mShops->create($metaReqs);

            return responseMsgs(true, "Successfully Saved", [$metaReqs], "050202", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {

            return responseMsgs(false, $e->getMessage(), [], "050202", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    public function calculateShopRate($shopCategoryId, $area, $financialYear)
    {
        $mMarShopRateList = new MarShopRateList();
        $base_rate = $mMarShopRateList->getShopRate($shopCategoryId, $financialYear);
        if ($shopCategoryId == 1) {
            $base_rate = 5;                                   // Get Base rate of BOT Shop financial yearwise
            return ($base_rate * $area * 12);                   // BOT Amount Calculation
        } else {
            $base_rate = 5;                                   // Get Base rate of City shop financial yearwise
            return ($base_rate * $area * 12);                   // BOT Amount Calculation
        }
    }

    /**
     * | ID Generation For Shop
     */
    public function shopIdGeneration($marketId)
    {
        $idDetails = DB::table('m_market')->select('shop_counter', 'market_name')->where('id', $marketId)->first();
        $market = strtoupper(substr($idDetails->market_name, 0, 3));
        $counter = $idDetails->shop_counter + 1;
        DB::table('m_market')->where('id', $marketId)->update(['shop_counter' => $counter]);
        return $id = "SHOP-" . $market . "-" . (1000 + $idDetails->shop_counter);
    }

    /**
     * | Edit shop Records
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
                'ulb_id' => $req->auth['ulb_id']
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
            return responseMsgs(true, "Successfully Updated", [], "050203", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050203, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }


    /**
     * | Edit Shop Data
     */
    public function editShopData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopId' => 'required|numeric',
            'contactNo' => 'required|numeric',
            'remarks' => 'nullable|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);

        try {
            $shopDetails = Shop::find($req->shopId);
            $shopDetails->contact_no=$req->contactNo;
            $shopDetails->remarks=$req->remarks;
            $shopDetails->save();
            return responseMsgs(true, "Update Shop Successfully !!!", '', 050204, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050204, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Shop Details By Id
     */
    public function show(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $details = $this->_mShops->getShopDetailById($req->id);
            if (collect($details)->isEmpty())
                throw new Exception("Shop Does Not Exists");
            // Basic Details
            $basicDetails = $this->generateBasicDetails($details);
            $shop['shopDetails'] = $basicDetails;
            $mMarShopDemand = new MarShopDemand();
            $demands = $mMarShopDemand->getDemandByShopId($req->id);
            $total = $demands->pluck('amount')->sum();
            $shop['demands'] = $demands;
            $shop['total'] = $total;

            $mMarShopPayment = new MarShopPayment();
            $payments = $mMarShopPayment->getPaidListByShopId($req->id);
            $totalPaid = $payments->pluck('amount')->sum();
            // $shop['payments'] = $payments;
            $shop['totalPaid'] = $totalPaid;
            $shop['pendingAmount'] = $total - $totalPaid;
            return responseMsgs(true, "", $shop, 050204, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050204, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | View All Shop Data
     */
    public function retrieve(Request $req)
    {
        try {
            $shops = $this->_mShops->retrieveAll();
            return responseMsgs(true, "", $shops, 050205, "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050205, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | View All Active Shops
     */
    public function retrieveAllActive(Request $req)
    {
        try {
            $shops = $this->_mShops->retrieveActive();
            return responseMsgs(true, "", $shops, 050206, "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050206, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Delete Shop by Id
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
            if (isset($req->status)) { // In Case of Deactivation or Activation
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
            // $metaReqs = [
            //     'status' => '0',
            // ];
            $Shops = $this->_mShops::findOrFail($req->id);
            $Shops->update($metaReqs);
            return responseMsgs(true, $message, [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | List Ulb Wise Circle
     */
    public function listUlbWiseCircle(Request $req)
    {
        try {
            $mMCircle = new MCircle();
            $list = $mMCircle->getCircleByUlbId($req->auth['ulb_id']);
            return responseMsgs(true, "Circle List Featch Successfully !!!", $list, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Market list Circle wise
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
            $list = $mMMarket->getMarketByCircleId($req->circleId);
            return responseMsgs(true, "Market List Featch Successfully !!!", $list, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }


    /**
     * | Get Shop list by Market Id
     */
    public function listShopByMarketId(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'marketId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $mShop = new Shop();
            $list = $mShop->getShop($req->marketId);
            if ($req->key)
                $list = searchShopRentalFilter($list, $req);
            //     $list = searchRentalFilter($list, $req);
            $list = paginator($list, $req);
            return responseMsgs(true, "Shop List Fetch Successfully !!!", $list, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get list Shop 
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
            return responseMsgs(true, "Shop List Fetch Successfully !!!", $list, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Shop Details By ID
     */
    public function getShopDetailById(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $details = $this->_mShops->getShopDetailById($req->id);
            if (collect($details)->isEmpty())
                throw new Exception("Shop Does Not Exists");
            // Basic Details
            $basicDetails = $this->generateBasicDetails($details);
            $shop['shopDetails'] = $basicDetails;
            $mMarShopDemand = new MarShopDemand();
            $demands = $mMarShopDemand->getDemandByShopId($req->id);
            $total = $demands->pluck('amount')->sum();
            $shop['demands'] = $demands;
            $shop['total'] = $total;
            return responseMsgs(true, "Shop Details Fetch Successfully !!!", $shop, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }


    /**
     * | Get Shop Collection Summery
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
            $list = $mShopPayment->paymentList($req->auth['ulb_id'])->whereBetween('payment_date', [$fromDate, $toDate]);
            $list = paginator($list, $req);
            $list['todayCollection'] = $mShopPayment->todayShopCollection($req->auth['ulb_id'], date('Y-m-d'))->get()->sum('amount');
            // $list['todayCollection']=500.02;
            return responseMsgs(true, "Shop Summary Fetch Successfully !!!", $list, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get TC Collection Datewise 
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

            $userDetails = json_decode($userDetails);
            // $data=$data->data;
            $list = collect($refValues)->map(function ($values) use ($totalCollection, $userDetails) {
                $ref['totalAmount'] = $totalCollection->where('user_id', $values)->sum('amount');
                $ref['userId'] = $values;
                // $ref['tcName'] = "ANCTC";
                $ref['tcName'] = collect($userDetails->data)->where('id', $values)->pluck('name')->first();
                return $ref;
            });
            $list1['list'] = $list->values();
            $list1['todayPayments'] = $todayTollPayment + $todayShopPayment;
            return responseMsgs(true, "TC Collection Fetch Successfully !!!", $list1, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Shop Payment By Admin
     */
    public function shopPaymentByAdmin(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate' => 'required|date_format:Y-m-d',
            'toDate' => 'required|date_format:Y-m-d',
            'shopNo' => 'required|string',
            'amount' => 'required|numeric',
            'due'    => 'required|numeric',
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
            $mShopPayment = new ShopPayment();
            $details = DB::table('mar_shops')->select('*')->where('shop_no', $req->shopNo)->first();
            if (!$details)
                throw new Exception("Shop Not Found !!!");
            $shopId = $details->id;
            $months = monthDiff($req->toDate, $req->fromDate) + 1;
            $req->merge(['months' => $months]);
            $paymentId = $mShopPayment->addPaymentByAdmin($req, $shopId);
            $mshop = new Shop();
            $mshopDetails = $mshop->find($shopId);
            $mshopDetails->last_tran_id = $paymentId;
            $mshopDetails->arrear = $req->due;
            $mshopDetails->save();
            return responseMsgs(true, "Payment Accept Successfully !!!", '', 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Payment Reciept
     */
    public function getPaymentReciept(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopId' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $mShop = new Shop();
            $reciept = $mShop->getReciept($req->shopId);
            return responseMsgs(true, "Payment Reciept Fetch Successfully !!!", $reciept, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | list shop type
     */
    public function listShopType(Request $req)
    {
        try {
            $mMarShopType = new MarShopType();
            $list = $mMarShopType->listShopType($req);
            return responseMsgs(true, "Shop Type List !!!", $list, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }


    public function shopMaster(Request $req)
    {
        try {
            $mMarShopType = new MarShopType();
            $mMCircle = new MCircle();
            $mShopConstruction = new ShopConstruction();

            $list['shopType'] = $mMarShopType->listShopType();
            $list['circleList'] = $mMCircle->getCircleByUlbId($req->auth['ulb_id']);
            $list['listConstruction'] = $mShopConstruction->listConstruction();
            $fYear = FyListdesc();
            $f_y = array();
            foreach ($fYear as $key => $fy) {
                $f_y[$key]['id'] = $fy;
                $f_y[$key]['financialYear'] = $fy;
            }
            $list['fYear'] = $f_y;
            return responseMsgs(true, "Shop Type List !!!", $list, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    public function getFinancialYear(Request $req)
    {
        try {
            $fylist = FyListdescForShop();
            return responseMsgs(true, "Financial Year List !!!", $fylist, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

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
            $list = $mShop->searchShopForPayment($req->shopCategoryId, $req->circleId, $req->marketId);
            return responseMsgs(true, "Payment Reciept Fetch Successfully !!!",  $list, 050207, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 050207, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    public function calculateShopRateFinancialwise(Request $req)
    {
        $shopPmtBll = new ShopPaymentBll();
        $validator = Validator::make($req->all(), [
            "shopId" => "required|integer",
            "fromFYear" => 'required|string',
            "toFYear" => 'required|string',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        // Business Logics
        try {
            $amount = $shopPmtBll->calculateRateFinancialYearWiae($req);
            return responseMsgs(true, "Amount Fetch Successfully", ['amount' => $amount], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    public function entryCheckOrDD(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopId' => 'required|integer',
            'bankName' => 'required|string',
            'branchName' => 'required|string',
            'chequeNo' => 'required|integer',
            "fromFYear" => 'required|string',
            "toFYear" => 'required|string',
            "paymentMode" => 'required|string',
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $mMarShopPayment = new MarShopPayment();
            $res = $mMarShopPayment->entryCheckDD($req);
            return responseMsgs(true, "Cheque or DD Entry Successfully", ['details' => $res], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    public function listEntryCheckorDD(Request $req)
    {
        try {
            $mMarShopPayment = new MarShopPayment();
            $data = $mMarShopPayment->listUnclearedCheckDD($req);
            $list = paginator($data, $req);
            return responseMsgs(true, "List Uncleared Check Or DD", $list, 055001, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Clear or Bounce Cheque or DD
     */
    public function clearOrBounceChequeOrDD(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'chequeId' => 'required|integer',
            'status' => 'required|integer',
            'remarks' => $req->status == 2 ? 'required|string' : 'nullable|string',
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $shopPayment = $mMarShopPayment = MarShopPayment::find($req->chequeId);
            $mMarShopPayment->payment_date = Carbon::now()->format('Y-m-d');
            $mMarShopPayment->payment_status = $req->status;
            $mMarShopPayment->save();
            if ($req->status == 1) {
                $UpdateDetails = MarShopDemand::where('shop_id',  $shopPayment->shop_id)
                    ->where('financial_year', '>=', $shopPayment->paid_from)
                    ->where('financial_year', '<=', $shopPayment->paid_to)
                    ->where('amount', '>', 0)
                    ->get();
                foreach ($UpdateDetails as $updateData) {
                    $updateRow = MarShopDemand::find($updateData->id);
                    $updateRow->payment_date = Carbon::now()->format('Y-m-d');
                    $updateRow->payment_status = 1;
                    $updateRow->tran_id = $req->chequeId;
                    $updateRow->save();
                }
            }
            if ($req->status)
                $msg = "Cheque Cleared Successfully !!!";
            else
                $msg = "Cheque Bounced !!!";
            return responseMsgs(true, $msg, '', 055001, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | List shop Collection
     */
    public function listShopCollection(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'shopCategoryId' => 'nullable|integer',
            'marketId' => 'nullable|integer',
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => 'nullable|date_format:Y-m-d|after_or_equal:fromDate',
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            if (!isset($req->fromDate))
                $fromDate = Carbon::now()->format('Y-m-d');
            else
                $fromDate = $req->fromDate;
            if (!isset($req->toDate))
                $toDate = Carbon::now()->format('Y-m-d');
            else
                $toDate = $req->toDate;
            $mMarShopPayment = new MarShopPayment();
            $data = $mMarShopPayment->listShopCollection($fromDate, $toDate);
            // $data = $mMarShopPayment->listShopCollection($req);
            if ($req->shopCategoryId != 0)
                $data = $data->where('shop_category_id', $req->shopCategoryId);
            if ($req->marketId != 0)
                $data = $data->where('market_id', $req->marketId);
            if ($req->auth['user_type'] == 'JSK' || $req->auth['user_type'] == 'TC')
                $data = $data->where('mar_shop_payments.user_id', $req->auth['id']);
            $list = paginator($data, $req);
            $list['collectAmount'] = $data->sum('amount');
            return responseMsgs(true, "Shop Collection List Fetch Succefully !!!", $list, 055001, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | DCB Reports of All Shops
     */
    public function dcbReports(Request $req){
        try{
            $shopType=MarShopType::select('shop_type','id')->where('status','1')->get();
            
            return responseMsgs(true, "DCB Reports !!!", $shopType, 055001, "1.0", responseTime(), "POST", $req->deviceId);
        }catch(Exception $e){
            return responseMsgs(false, $e->getMessage(), [], 055001, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }
}

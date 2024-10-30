<?php

namespace App\BLL\Market;

use App\Models\Rentals\MarShopDemand;
use App\Models\Rentals\Shop;
use App\Models\Rentals\ShopPayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * | Created On-14-06-2023 
 * | Author-Anshu Kumar
 * | Status-Open
 */
class ShopPaymentBll
{
    private $_mShopPayments;
    public $_shopDetails;
    public $_tranId;
    private $_shopLastDemand;
    private $_mShopDemand;
    private $_now;


    public function __construct()
    {
        $this->_mShopPayments = new ShopPayment();
        $this->_mShopDemand   = new MarShopDemand();
        $this->_now           = Carbon::now();
    }

    /**
     * | Shop Payments
     * | @param Request $req
     */
    public function shopPayment_old($req)
    {
        // Business Logics
        $paymentTo = Carbon::parse($req->paymentTo);
        if (!isset($this->_tranId))                                 // If Last Transaction Not Found
        {
            $paymentFrom = Carbon::parse($req->paymentFrom);
            $diffInMonths = $paymentFrom->diffInMonths($paymentTo);
            $totalMonths = $diffInMonths + 1;
        }

        if (isset($this->_tranId)) {                                // If Last Transaction ID is Available
            $shopLastPayment = $this->_mShopPayments::findOrFail($this->_tranId);
            $paymentFrom = Carbon::parse($shopLastPayment->paid_to);
            $diffInMonths = $paymentFrom->diffInMonths($paymentTo);
            $totalMonths = $diffInMonths + 1;
        }

        // $payableAmt = ($this->_shopDetails->rate * $totalMonths) + $this->_shopDetails->arrear;
        // $amount = $req->amount;
        $payableAmt = ($this->_shopDetails->rate * $totalMonths);
        $amount = $payableAmt;
        $arrear = $payableAmt - $amount;
        if ($payableAmt < 1)
            throw new Exception("Dues Not Available");
        // Insert Payment 
        $paymentReqs = [
            'shop_id' => $req->shopId,
            'paid_from' => $paymentFrom,
            'paid_to' => $paymentTo,
            'demand' => $payableAmt,
            'amount' => $amount,
            'rate' => $this->_shopDetails->rate,
            'months' => $totalMonths,
            'payment_date' => Carbon::now(),
            'user_id' => $req->auth['id'] ?? 0,
            'ulb_id' => $this->_shopDetails->ulb_id,
            'remarks' => $req->remarks
        ];
        DB::beginTransaction();
        $createdPayment = $this->_mShopPayments::create($paymentReqs);
        $this->_shopDetails->update([
            'last_tran_id' => $createdPayment->id,
            'arrear' => $arrear
        ]);
        return $amount;
    }

    /* | Shop Payments
    * | @param Request $req
    */
    public function shopPayment($req)
    {
        // Calculate Amount For Payment
        $user = authUser($req);
        $amount = DB::table('mar_shop_demands')
            ->where('shop_id', $req->shopId)
            ->where('payment_status', 0)
            ->where('financial_year', '<=', $req->toFYear)
            ->orderBy('financial_year', 'ASC')
            ->sum('amount');
        if ($amount < 1)
            throw new Exception("No Any Due Amount !!!");
        $shopDetails = DB::table('mar_shops')->select('*')->where('id', $req->shopId)->first();                                     // Get Shop Details
        // Get All Financial Year For Payment
        $financialYear = DB::table('mar_shop_demands')
            ->where('shop_id', $req->shopId)
            ->where('payment_status', 0)
            ->where('amount', '>', '0')
            ->where('financial_year', '<=', $req->toFYear)
            ->orderBy('financial_year', 'ASC')
            ->first('financial_year');

        // Insert Payment Records 
        $paymentReqs = [
            'shop_id' => $req->shopId,
            'amount' => $amount,
            'paid_from' => $financialYear->financial_year,
            'paid_to' => $req->toFYear,
            'payment_date' => Carbon::now(),
            'payment_status' => '1',
            // 'user_id' => $req->auth['id'] ?? $req->userId ?? 0,
            'user_id' => $user->id,
            'ulb_id' => $shopDetails->ulb_id,
            'remarks' => $req->remarks,
            'pmt_mode' => $req->paymentMode,
            'shop_category_id' => $shopDetails->shop_category_id,
            'transaction_id' => time() . $shopDetails->ulb_id . $req->shopId,                   // Transaction id is a combination of time funcation in PHP and ULB ID and Shop ID
        ];
        DB::beginTransaction();
        $createdPayment = $this->_mShopPayments::create($paymentReqs);                          // Insert Payment Records in Payment Table
        $mshop = Shop::find($req->shopId);
        $tranId=$mshop->last_tran_id = $createdPayment->id;
        $mshop->save();

        $UpdateDetails = MarShopDemand::where('shop_id', $req->shopId)                         // Get All demand of Selected financial Year After Payment Success
            ->where('financial_year', '>=', $financialYear->financial_year)
            ->where('financial_year', '<=', $req->toFYear)
            ->orderBy('financial_year', 'ASC')
            ->where('amount', '>', '0')
            ->get();

        // Update All Payment Demand After Payment Success
        foreach ($UpdateDetails as $updateData) {
            // return $updateData->id; die;
            $updateRow = MarShopDemand::find($updateData->id);
            $updateRow->payment_date = Carbon::now()->format('Y-m-d');
            $updateRow->payment_status = 1;
            $updateRow->tran_id = $createdPayment->id;
            $updateRow->save();
        }
        $mShop = Shop::find($req->shopId);
        $ret['shopDetails']=$mShop;
        $ret['amount']=$amount;
        $ret['paymentDate']=Carbon::now()->format('d-m-Y');
        $ret['allottee']=$mShop->allottee;
        $ret['mobile']=$mShop->contact_no;
        $ret['tranId']=$tranId;
        return $ret;
    }

    /**
     * | Calculate rate between two financial year 
     */
    public function calculateRateFinancialYearWiae($req)
    {
        return  DB::table('mar_shop_demands')
            ->where('shop_id', $req->shopId)
            ->where('payment_status', 0)
            ->where('financial_year', '<=', $req->toFYear)
            ->sum('amount');
    }

    /**
     * | Shop demand
     * | @param Request $req
     */
    public function getActiveShop(Request $req)
    {
        try{
            $currentYm = Carbon::now()->format("Y-m");
            $sql = "with demand_genrated as (
                select distinct shop_id
                from mar_shop_demands
                where status =1 and  TO_CHAR(cast(monthly as date),'YYYY-MM') = TO_CHAR(CURRENT_DATE,'YYYY-MM')
            )
            select id
            from mar_shops
            left join demand_genrated on demand_genrated.shop_id = mar_shops.id
            where status =1 AND demand_genrated.shop_id IS null ";
            $data = DB::select($sql);
            $excelData=[
                "shopId","status","errors","response",
            ];
            $size = collect($data)->count("id");
            foreach($data as $key=>$val)
            {
                DB::beginTransaction();
                echo"=========index( ".$key." [remain---->".($size - $key)."]  ".$val->id.")===========\n\n";
                $newReq = new Request(["shopId"=>$val->id]);
                $exrow["shopId"]=$val->id;
                $respons = null;
                try{
                    $respons = $this->shopDemand($newReq);
                    $exrow["status"]="Success";
                    DB::commit();
                    DB::commit();
                }
                catch(Exception $e)
                {
                    DB::rollBack();
                    DB::rollBack();
                    $exrow["status"]="Faild";
                    $exrow["error"]=$e->getMessage();
                }
                echo("=======".$exrow["status"]."=======\n\n");
                $exrow["response"]=json_decode($respons??"");
                array_push($excelData,$exrow);
            }
            echo"=========end===========\n";
            print_var($excelData);
        }
        catch(Exception $e)
        {
            dd("fatel Error",$e->getMessage(),$e->getFile(),$e->getLine());
        }
    }
    /**
     * | generate shop demands yearly
     * | 
     */
    public function shopDemand($req)
    {
        // Get the current month
        $currentMonth = Carbon::now()->startOfMonth();

        $shopDetails = Shop::find($req->shopId);
        #check shop last demand 
        $this->_shopLastDemand = $this->_mShopDemand->CheckConsumerDemand($req)->get()->sortByDesc("financial_year")->first();;

        if ($this->_shopLastDemand) {
            $lastDemandMonth = Carbon::parse($this->_shopLastDemand->monthly)->startOfMonth();
            if ($lastDemandMonth->eq($currentMonth)) {
                throw new Exception("Demand is already generated for this month.");
            }
        }
        if ($this->_shopLastDemand) {
            $startDate          = Carbon::parse($this->_shopLastDemand->monthly);
            $endDate            = Carbon::parse($this->_now);
        }
        # If the demand is generated for the first time
        else {
            $endDate            = Carbon::parse($this->_now);
            $startDate          = Carbon::parse($shopDetails->created_at);
        }

        $demandFrom = Carbon::parse($startDate);
        $months = [];
        $currentMonth = $demandFrom->copy()->startOfMonth();
        while ($currentMonth->lte($endDate)) {
            $months[] = $currentMonth->format('Y-m-d');
            $currentMonth->addMonth();
        }
        DB::beginTransaction();
        foreach ($months as $month) {
            $amount = $shopDetails->rate;                                        // rate is fixed for each month
            $payableAmt = $amount;
            $arrear = $amount;
            // Insert demand
            $demandReqs = [
                'shop_id' => $req->shopId,
                'amount' => $amount,
                'monthly' => $month,
                'payment_date' => Carbon::now(),
                'user_id' => $req->auth['id'] ?? 0,
                'ulb_id' => $shopDetails->ulb_id,
            ];
            $this->_mShopDemand::create($demandReqs);
        }
    }

}

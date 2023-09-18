<?php

namespace App\BLL\Market;

use App\Models\Rentals\MarShopDemand;
use App\Models\Rentals\Shop;
use App\Models\Rentals\ShopPayment;
use Carbon\Carbon;
use Exception;
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

    public function __construct()
    {
        $this->_mShopPayments = new ShopPayment();
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
        $amount = DB::table('mar_shop_demands')
            ->where('shop_id', $req->shopId)
            ->where('payment_status', 0)
            ->where('financial_year', '>=', $req->fromFYear)
            ->where('financial_year', '<=', $req->toFYear)
            ->sum('amount');
        if ($amount < 1)
            throw new Exception("No Any Due Amount !!!");
        $shopDetails = DB::table('mar_shops')->select('*')->where('id', $req->shopId)->first();
        // $payableAmt = $req->amount;
        // $amount = $payableAmt;

        // Insert Payment 
        $paymentReqs = [
            'shop_id' => $req->shopId,
            'amount' => $amount,
            'paid_from' => $req->fromFYear,
            'paid_to' => $req->toFYear,
            'payment_date' => Carbon::now(),
            'payment_status' => '1',
            'user_id' => $req->auth['id'] ?? 0,
            'ulb_id' => $shopDetails->ulb_id,
            'remarks' => $req->remarks,
            'pmt_mode' => $req->paymentMode,
            'shop_category_id' => $shopDetails->shop_category_id,
            'transaction_id' => time() . $shopDetails->ulb_id . $req->shopId,     // Transaction id is a combination of time funcation in PHP and ULB ID and Shop ID
        ];
        DB::beginTransaction();
        $createdPayment = $this->_mShopPayments::create($paymentReqs);
        $mshop = Shop::find($req->shopId);
        $mshop->last_tran_id = $createdPayment->id;
        $mshop->save();

        $UpdateDetails = MarShopDemand::where('shop_id', $req->shopId)
            ->where('financial_year', '>=', $req->fromFYear)
            ->where('financial_year', '<=', $req->toFYear)
            ->where('amount', '>', '0')
            ->get();
        foreach ($UpdateDetails as $updateData) {
            // return $updateData->id; die;
            $updateRow = MarShopDemand::find($updateData->id);
            $updateRow->payment_date = Carbon::now()->format('Y-m-d');
            $updateRow->payment_status = 1;
            $updateRow->tran_id = $createdPayment->id;
            $updateRow->save();
        }
        $mShop=Shop::find($req->shopId);
        return $amount;
    }

    /**
     * | 
     */
    public function calculateRateFinancialYearWiae($req)
    {
        return  DB::table('mar_shop_demands')
            ->where('shop_id', $req->shopId)
            ->where('payment_status', 0)
            ->where('financial_year', '>=', $req->fromFYear)
            ->where('financial_year', '<=', $req->toFYear)
            ->sum('amount');
    }
}

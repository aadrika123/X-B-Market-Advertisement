<?php

namespace App\Models\Rentals;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarShopPayment extends Model
{
   use HasFactory;
   protected $guarded = [];

   /**
    * | Get Paid List By Shop Id
    */
   public function getPaidListByShopId($shopId)
   {
      return self::select(
         'shop_id',
         'amount',
         'pmt_mode as payment_mode',
         DB::raw("TO_CHAR(payment_date, 'DD-MM-YYYY') as payment_date")
      )
         ->where('shop_id', $shopId)
         ->whereIN('payment_status', [1, 2])
         // ->where(function($where){
         //    $where->Orwhere("payment_status",1)
         //    ->Orwhere("payment_status",2);
         // })
         ->get();
   }

   /**
    * | Entry Check or DD
    */
   public function entryCheckDD($req)
   {
      // Get Amount For Payment
      $amount = DB::table('mar_shop_demands')
         ->where('shop_id', $req->shopId)
         ->where('payment_status', 0)
         ->where('financial_year', '<=', $req->toFYear)
         ->orderBy('financial_year', 'ASC')
         ->sum('amount');
      if ($amount < 1)
         throw new Exception("No Any Due Amount !!!");
      $shopDetails = DB::table('mar_shops')->select('*')->where('id', $req->shopId)->first();                                       // Get Shop Details For Payment

      $financialYear = DB::table('mar_shop_demands')                                                                                // Get First Financial Year where Payment start
         ->where('shop_id', $req->shopId)
         ->where('payment_status', 0)
         ->where('amount', '>', '0')
         ->where('financial_year', '<=', $req->toFYear)
         ->orderBy('financial_year', 'ASC')
         ->first('financial_year');

      // Make payment Records for insert in pyment Table
      $paymentReqs = [
         'shop_id' => $req->shopId,
         'amount' => $amount,
         'paid_from' => $financialYear->financial_year,
         'paid_to' => $req->toFYear,
         'cheque_date' => $req->chequeDdDate,
         'payment_date' => Carbon::now()->format('Y-m-d'),
         'bank_name' => $req->bankName,
         'branch_name' => $req->branchName,
         'cheque_no' => $req->chequeNo,
         'dd_no' => $req->ddNo,
         'user_id' => $req->auth['id'] ?? 0,
         'ulb_id' => $shopDetails->ulb_id,
         'shop_category_id' => $shopDetails->shop_category_id,
         'remarks' => $req->remarks,
         'payment_status' => 2,
         'pmt_mode' => $req->paymentMode,
         'transaction_id' => time() . $shopDetails->ulb_id . $req->shopId,     // Transaction id is a combination of time funcation in PHP and ULB ID and Shop ID
         'photo_path_absolute' => $req->photo_path_absolute,
         'photo_path' => $req->photo_path,
      ];
      $createdPayment = MarShopPayment::create($paymentReqs);

      // update shop table with payment transaction ID
      $mshop = Shop::find($createdPayment->shop_id);
      $mshop->last_tran_id = $createdPayment->id;
      $mshop->save();

      // Get All Demand for cheque Payment
      $UpdateDetails = MarShopDemand::where('shop_id',  $req->shopId)
         ->where('financial_year', '>=', $financialYear->financial_year)
         ->where('financial_year', '<=',  $req->toFYear)
         ->where('amount', '>', 0)
         ->orderBy('financial_year', 'ASC')
         ->get();
      // Update All Demand for cheque Payment
      foreach ($UpdateDetails as $updateData) {
         $updateRow = MarShopDemand::find($updateData->id);
         $updateRow->payment_date = Carbon::now()->format('Y-m-d');
         $updateRow->payment_status = 1;
         $updateRow->tran_id = $createdPayment->id;
         $updateRow->save();
      }
      $shop['createdPayment'] = $createdPayment;
      $shop['shopDetails'] = $mshop;
      $shop['amount'] = $amount;
      $shop['lastTranId'] = $createdPayment->id;
      return $shop;
   }

   /**
    * | List Uncleared cheque or DD
    */
   public function listUnclearedCheckDD($req)
   {
      return  DB::table('mar_shop_payments')
         ->select(
            'mar_shop_payments.id',
            'mar_shop_payments.payment_date',
            'mar_shop_payments.amount',
            'mar_shop_payments.paid_from',
            'mar_shop_payments.paid_to',
            'mar_shop_payments.cheque_no',
            //   'mar_shop_payments.cheque_date as recieve_date',
            DB::raw("TO_CHAR(mar_shop_payments.cheque_date, 'DD-MM-YYYY') as recieve_date"),
            'mar_shop_payments.bank_name',
            'mar_shop_payments.branch_name',
            't1.shop_no',
            't1.allottee',
            't1.contact_no'
         )
         ->join('mar_shops as t1', 'mar_shop_payments.shop_id', '=', 't1.id')
         ->where('payment_status', '2')
         ->where('cheque_date', '!=', NULL);
   }


   /**
    * | List Uncleared cheque or DD
    */
   public function listUnverifiedCashPayment($req)
   {
      return  DB::table('mar_shop_payments')
         ->select(
            'mar_shop_payments.id',
            'mar_shop_payments.payment_date',
            'mar_shop_payments.amount',
            'mar_shop_payments.paid_from',
            'mar_shop_payments.paid_to',
            DB::raw("TO_CHAR(mar_shop_payments.cheque_date, 'DD-MM-YYYY') as recieve_date"),
            't1.shop_no',
            't1.allottee',
            't1.contact_no'
         )
         ->join('mar_shops as t1', 'mar_shop_payments.shop_id', '=', 't1.id')
         ->where('is_verified', '=', '0')
         ->where('pmt_mode', '=', 'CASH');
   }

   /**
    * | update payment status for clear or bounce cheque
    */
   public function clearBounceCheque()
   {
   }

   /**
    * | List of shop collection between two given date
    */
   public function listShopCollection($fromDate, $toDate)
   {
      return DB::table('mar_shop_payments')
         ->select(
            'mar_shop_payments.amount',
            'mar_shop_payments.user_id as collected_by',
            DB::raw("TO_CHAR(mar_shop_payments.payment_date, 'DD-MM-YYYY') as payment_date"),
            'mar_shop_payments.paid_from',
            'mar_shop_payments.paid_to',
            't2.shop_category_id',
            't2.shop_no',
            't2.allottee',
            't2.market_id',
            'mst.shop_type',
            'mkt.market_name',
            'mc.circle_name'
         )
         ->leftjoin('mar_shops as t2', 't2.id', '=', 'mar_shop_payments.shop_id')
         ->leftjoin('mar_shop_types as mst', 'mst.id', '=', 't2.shop_category_id')
         ->leftjoin('m_circle as mc', 'mc.id', '=', 't2.circle_id')
         ->leftjoin('m_market as mkt', 'mkt.id', '=', 't2.market_id')
         ->where('mar_shop_payments.payment_date', '>=', $fromDate)
         ->where('mar_shop_payments.payment_date', '<=', $toDate)
         ->where('mar_shop_payments.payment_status', '1');
   }

   /**
    * | find total collection shop type wise
    */
   public function totalCollectoion($shopType)
   {
      return MarShopPayment::select('*')->where('payment_status', '1')->where('shop_category_id', $shopType)->where('shop_category_id', $shopType)->sum('amount');
   }
   /**
    * | find total Arrear collection shop type wise
    */
   public function totalArrearCollectoion($shopType, $currentYear)
   {
      return MarShopDemand::select('*')->where('payment_status', '1')->where('financial_year', '<', $currentYear)->where('shop_category_id', $shopType)->sum('amount');
   }
   /**
    * | find total Current collection shop type wise
    */
   public function totalCurrentCollectoion($shopType, $currentYear)
   {
      return MarShopDemand::select('*')->where('payment_status', '1')->where('financial_year', '=', $currentYear)->where('shop_category_id', $shopType)->sum('amount');
   }

   /**
    * | Get Shop Wise All Payments details For DCB Reports 
    */
   public function shopCollectoion($shopId)
   {
      return MarShopPayment::select('*')->where('payment_status', '1')->where('shop_id', $shopId)->sum('amount');
   }

   /**
    * | Find Request Details By Request Refferal Number
    */
   public function findByReqRefNo($reqRefNo)
   {
      return self::where('req_ref_no', $reqRefNo)->first();
   }

   /**
    * | Get List of All Payment
    */
   public function getListOfPaymentDetails()
   {
      return  DB::table('mar_shop_payments')
         ->select(
            'mar_shop_payments.id',
            'mar_shop_payments.payment_date',
            'mar_shop_payments.pmt_mode as payment_mode',
            'mar_shop_payments.amount',
            'mar_shop_payments.paid_from',
            'mar_shop_payments.paid_to',
            'mar_shop_payments.cheque_no',
            'mar_shop_payments.dd_no',
            'mar_shop_payments.bank_name',
            'mar_shop_payments.transaction_id as transaction_no',
            DB::raw("TO_CHAR(mar_shop_payments.cheque_date, 'DD-MM-YYYY') as recieve_date"),
            't1.shop_no',
            't1.allottee',
            't1.contact_no',
            'user.name as collector_name',
            'user.id as tc_id',
         )
         ->join('mar_shops as t1', 'mar_shop_payments.shop_id', '=', 't1.id')
         ->join('users as user', 'user.id', '=', 'mar_shop_payments.user_id')
         ->where('mar_shop_payments.pmt_mode', '!=', "ONLINE")
         ->where('mar_shop_payments.payment_status', '!=', "3");
   }


   /**
    * | Get Collection Report Tc Wise
    */
   public function getListOfPayment()
   {
      return  DB::table('mar_shop_payments')
         ->select(
            DB::raw('sum(mar_shop_payments.amount) as total_amount'),
            'mar_shop_payments.user_id as tc_id',
            'user.name as tc_name',
            'user.mobile as tc_mobile',
            't1.circle_id',
         )
         ->join('mar_shops as t1', 'mar_shop_payments.shop_id', '=', 't1.id')
         ->join('users as user', 'user.id', '=', 'mar_shop_payments.user_id')
         ->where('mar_shop_payments.pmt_mode', '!=', "ONLINE")
         ->where('mar_shop_payments.payment_status', '!=', "3");
   }


   /**
    * | Get Toll Payment List ULB Wise
    */
   public function paymentList($ulbId)
   {
      return self::select(
         'mar_shop_payments.*',
         'ms.shop_no',
         'mc.circle_name',
         'mm.market_name',
         'ms.allottee as vendor_name',
         'ms.contact_no as mobile',
         DB::raw("TO_CHAR(mar_shop_payments.payment_date, 'DD-MM-YYYY') as payment_date"),
      )
         ->join('mar_shops as ms', 'ms.id', '=', 'mar_shop_payments.shop_id')
         ->join('m_circle as mc', 'mc.id', '=', 'ms.circle_id')
         ->join('m_market as mm', 'mm.id', '=', 'ms.market_id')
         ->where('mar_shop_payments.ulb_id', $ulbId);
   }

   /**
    * | Get Shop Payment List mode wise
    */
   public function listShopPaymentSummaryByPaymentMode($dateFrom, $dateTo)
   {
      return self::select(
         // 'mar_shop_payments.shop_category_id',
         'mar_shop_payments.pmt_mode',
         // 'mst.shop_type',
         // 'mm.id as market_id',
         // 'mm.market_name',
         DB::raw('sum(mar_shop_payments.amount) as total_amount'),
         DB::raw('count(mar_shop_payments.id) as total_no_of_transaction'),
      )
         ->join('mar_shop_types as mst', 'mst.id', '=', 'mar_shop_payments.shop_category_id')
         ->join('mar_shops as ms', 'ms.id', '=', 'mar_shop_payments.shop_id')
         ->join('m_market as mm', 'mm.id', '=', 'ms.market_id')
         ->where('payment_status', '1')
         ->whereBetween('payment_date', [$dateFrom, $dateTo]);
      // ->groupBy('pmt_mode','mar_shop_payments.shop_category_id','mst.shop_type','mm.id','mm.market_name');
      // ->groupBy('pmt_mode','mm.id');
   }

   /**
    * | List shop collection summary
    */
   public function listShopCollectionSummary($dateFrom, $dateTo)
   {
      return self::select(
         'mar_shop_payments.shop_category_id',
         'mst.shop_type',
         'ms.market_id',
         'mm.market_name',
         DB::raw('sum(mar_shop_payments.amount) as total_amount'),
         DB::raw('count(mar_shop_payments.id) as total_no_of_transaction'),
      )
         ->join('mar_shop_types as mst', 'mst.id', '=', 'mar_shop_payments.shop_category_id')
         ->join('mar_shops as ms', 'ms.id', '=', 'mar_shop_payments.shop_id')
         ->join('m_market as mm', 'mm.id', '=', 'ms.market_id')
         ->where('payment_status', '1')
         ->whereBetween('payment_date', [$dateFrom, $dateTo]);
      // ->groupBy('pmt_mode','mar_shop_payments.payment_date','mar_shop_payments.user_id','mar_shop_payments.shop_category_id','mst.shop_type');
   }

   public function searchTransaction($tranNo)
   {
      return Self::select(
         'mar_shop_payments.id',
         'mar_shop_payments.transaction_id as transaction_no',
         'mar_shop_payments.amount',
         'mar_shop_payments.payment_date',
         'mar_shop_payments.pmt_mode as payment_mode',
         'mar_shop_payments.dd_no',
         'mar_shop_payments.bank_name',
         'mar_shop_payments.cheque_no',
         DB::raw("TO_CHAR(mar_shop_payments.payment_date, 'DD-MM-YYYY') as payment_date"),
         DB::raw("'Municipal Rental' as type"),
      )
      ->where('transaction_id', $tranNo)
      ->where('payment_status', '!=', '0')
         // ->where('is_verified','0')
         ->get();
   }
   /**
    * | Transaction De-activation
    */
   public function deActiveTransaction($req)
   {
      $tranDetails = $tran = Self::find($req->tranId);
      $tran->payment_status = 0;
      $tran->deactive_date = Carbon::now();
      $tran->deactive_reason = $req->deactiveReason;
      $tran->save();
      $demandids = MarShopDemand::select('id')->where('shop_id', $tranDetails->shop_id)->whereBetween('financial_year', [$tranDetails->paid_from, $tranDetails->paid_to])->get();
      $updateData = [
         'payment_status' => '0',
         'payment_date' => NULL,
         'tran_id' => NULL
      ];

      return MarShopDemand::whereIn('id', $demandids)
         ->update($updateData);
   }

   public function listDeActiveTransaction(){
      return Self::select(
         'mar_shop_payments.id',
         'mar_shop_payments.transaction_id as transaction_no',
         'mar_shop_payments.amount',
         'mar_shop_payments.payment_date',
         'mar_shop_payments.pmt_mode as payment_mode',
         'mar_shop_payments.dd_no',
         'mar_shop_payments.bank_name',
         'mar_shop_payments.cheque_no',
         'mar_shop_payments.deactive_reason',
         DB::raw("TO_CHAR(mar_shop_payments.payment_date, 'DD-MM-YYYY') as payment_date"),
         DB::raw("TO_CHAR(mar_shop_payments.deactive_date, 'DD-MM-YYYY') as deactive_date"),
         DB::raw("'Municipal Rental' as type"),
      )
         ->where('payment_status', '0')->where('deactive_reason','!=',NULL);
         // ->where('is_verified','0')
         // ->get();
   }
}

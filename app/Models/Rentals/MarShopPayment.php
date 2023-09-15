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
      return self::select('shop_id', 'amount', 'pmt_mode as payment_mode', DB::raw("TO_CHAR(payment_date, 'DD-MM-YYYY') as payment_date"))->where('shop_id', $shopId)->is_active('id', '1')->get();
   }


   /**
    * | Entry Check or DD
    */
   public function entryCheckDD($req)
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
      $paymentReqs = [
         'shop_id' => $req->shopId,
         'amount' => $amount,
         'paid_from' => $req->fromFYear,
         'paid_to' => $req->toFYear,
         'cheque_date' => Carbon::now(),
         'bank_name' => $req->bankName,
         'branch_name' => $req->branchName,
         'cheque_no' => $req->chequeNo,
         'user_id' => $req->auth['id'] ?? 0,
         'ulb_id' => $shopDetails->ulb_id,
         'remarks' => $req->remarks,
         'is_active' => 0,
         'pmt_mode' => $req->paymentMode,
         'transaction_id' => time() . $shopDetails->ulb_id . $req->shopId,     // Transaction id is a combination of time funcation in PHP and ULB ID and Shop ID
      ];
      return $createdPayment = MarShopPayment::create($paymentReqs);
   }

   public function listUnclearedCheckDD($req)
   {
      return  DB::table('mar_shop_payments')
         ->select(
            'mar_shop_payments.id',
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
         ->where('is_active', '0')
         ->where('cheque_date', '!=', NULL);
   }
}

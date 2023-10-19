<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarTollPayment extends Model
{
  use HasFactory;
  protected $guarded = [];

  /**
   * | Get Toll Payment List ULB Wise
   */
  public function paymentList($ulbId)
  {
    return self::select(
      'mar_toll_payments.*',
      'mt.toll_no',
      'mc.circle_name',
      'mm.market_name',
      'mt.vendor_name',
      'mt.mobile',
      DB::raw("TO_CHAR(mar_toll_payments.payment_date, 'DD-MM-YYYY') as payment_date"),
    )
      ->join('mar_tolls as mt', 'mt.id', '=', 'mar_toll_payments.toll_id')
      ->join('m_circle as mc', 'mc.id', '=', 'mt.circle_id')
      ->join('m_market as mm', 'mm.id', '=', 'mt.market_id')
      ->where('mar_toll_payments.ulb_id', $ulbId);
  }

  /**
   * | Get All Payment List ULB wise
   */
  public function paymentListForTcCollection($ulbId)
  {
    return self::select('user_id', 'payment_date', 'amount')->where('ulb_id', $ulbId);
  }

  /**
   * | Get Today Payment List
   */
  public function todayTallCollection($ulbId, $date)
  {
    return self::select('amount')
      ->where('ulb_id', $ulbId)
      ->where('payment_date', $date);
    //  ->sum('amount');
  }

  /**
   * | Payment Accept By Admin
   */
  public function addPaymentByAdmin($req, $shopId)
  {
    $metaReqs = $this->metaReqs($req, $shopId);
    return self::create($metaReqs)->id;
  }

  /**
   * | Make Meta Request For Payment By Admin
   */
  public function metaReqs($req, $tollId)
  {
    return [
      "toll_id" => $tollId,
      "from_date" => $req->fromDate,
      "to_date" => $req->fromDate,
      "amount" => $req->amount,
      "pmt_mode" => "CASH",
      "rate" => $req->rate,
      "payment_date" => $req->paymentDate,
      "remarks" => $req->remarks,
      "is_active" => '1',
      "ulb_id" => $req->auth['ulb_id'],
      "collected_by" => $req->collectedBy,
      "reciepts" => $req->reciepts,
      "absolute_path" => $req->absolutePath,
    ];
  }

  /**
   * | Get TC Wise collection Report
   */
  public function tcWiseCollection($ulbId)
  {
    return self::select(DB::raw('sum(amount) as collectionAmount'), 'user.name as tcName', 'user.mobile as tcMobile')
      ->join('users as user', 'user.id', '=', 'mar_toll_payments.user_id')
      ->where('mar_toll_payments.ulb_id', $ulbId)
      ->groupBy(
        'mar_toll_payments.user_id',
        'user.name',
        'user.mobile',
      );
  }

  /**
   * | Get All COllection Summery
   */
  public function collectionSummary($ulbId)
  {
    return self::select('user.name as tcName', 'user.mobile as tcMobile','mt.toll_no','mt.vendor_name','mc.circle_name','mm.market_name','mar_toll_payments.payment_date','mar_toll_payments.amount','mar_toll_payments.from_date as dateFrom','mar_toll_payments.to_date as to_date','mt.circle_id','mt.market_id')
      ->join('users as user', 'user.id', '=', 'mar_toll_payments.user_id')
      ->join('mar_tolls as mt', 'mt.id', '=', 'mar_toll_payments.toll_id')
      ->join('m_circle as mc', 'mc.id', '=', 'mt.circle_id')
      ->join('m_market as mm', 'mm.id', '=', 'mt.market_id')
      ->where('mar_toll_payments.ulb_id', $ulbId);
     
  }
}

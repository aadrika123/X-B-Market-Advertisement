<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ShopPayment extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'mar_shop_payments';

    public function paymentList($ulbId)
    {
        return self::select(
                          'mar_shop_payments.*',
                          'ms.shop_no',
                          'mc.circle_name',
                          'mm.market_name',
                          DB::raw("TO_CHAR(mar_shop_payments.payment_date, 'DD-MM-YYYY') as payment_date"),
                          )
                    ->join('mar_shops as ms','ms.id','=','mar_shop_payments.shop_id')
                    ->join('m_circle as mc','mc.id','=','ms.circle_id')
                    ->join('m_market as mm','mm.id','=','ms.market_id')
                    ->where('mar_shop_payments.ulb_id',$ulbId);
    }

    
    public function todayShopCollection($ulbId,$date){
        return self::select('amount')
                    ->where('ulb_id', $ulbId)
                    ->where('payment_date', $date);
            //  ->sum('amount');
    }

    public function paymentListForTcCollection($ulbId){
        return self::select('user_id','payment_date','amount')->where('ulb_id',$ulbId);
    }


    

  /**
   * | Payment Accept By Admin
   */
  public function addPaymentByAdmin($req,$shopId){
    $metaReqs=$this->metaReqs($req,$shopId);
    // dd($metaReqs);
    return self::create($metaReqs)->id;

  }

  public function metaReqs($req,$shopId){
    return [
      "shop_id"=>$shopId,
      "paid_from"=>$req->fromDate,
      "paid_to"=>$req->fromDate,
      "amount"=>$req->amount,
      "pmt_mode"=>"CASH",
      "rate"=>$req->rate,
      "payment_date"=>$req->paymentDate,
      "remarks"=>$req->remarks,
      "is_active"=>'1',
      "ulb_id"=>$req->auth['ulb_id'],
      "collected_by"=>$req->collectedBy,
      "reciepts"=>$req->reciepts,
      "absolute_path"=>$req->absolutePath,
      "months"=>$req->months,
      "demand"=>$req->months*$req->rate,
    ];
  }

}

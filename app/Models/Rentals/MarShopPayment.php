<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarShopPayment extends Model
{
    use HasFactory;
     protected $guarded=[];

   /**
    * | Get Paid List By Shop Id
    */
     public function getPaidListByShopId($shopId){
        return self::select('shop_id','amount','pmt_mode as payment_mode',DB::raw("TO_CHAR(payment_date, 'DD-MM-YYYY') as payment_date"))->where('shop_id',$shopId)->get();
     }


}

<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarShopDemand extends Model
{
    use HasFactory;
    protected $guarded=[];
    public function getDemandByShopId($shopId){
        return self::select('financial_year','amount','payment_status',DB::raw("TO_CHAR(payment_date, 'DD-MM-YYYY') as payment_date"),'tran_id')->where('shop_id',$shopId)->get();
    }
}

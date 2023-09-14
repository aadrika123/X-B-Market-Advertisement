<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarShopRateList extends Model
{
    use HasFactory;

    protected $guarded=[];
    public function getShopRate($shopCategoryId,$financialYear){
        return DB::where(['shop_type_id'=>$shopCategoryId,'financial_year'=>$financialYear])->first('curent_rate');
    }
}

<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarShopDemand extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    /**
     * | Get Generated Demand Details Shop Wise
     */
    public function getDemandByShopId($shopId)
    {
        return self::select('financial_year', 'amount', 'payment_status', DB::raw("TO_CHAR(payment_date, 'DD-MM-YYYY') as payment_date"), 'tran_id')->where('shop_id', $shopId)->get();
    }

    /**
     * | Total Demand Generated shop category wise
     */
    public function totalDemand($shopType)
    {
        return MarShopDemand::select('*')->where('shop_category_id', $shopType)->where('status', '1')->sum('amount');
    }

    /**
     * | Get Shop All Generated Demands For DCB Reports
     */
    public function shopDemand($shopId)
    {
        return MarShopDemand::select('*')->where('shop_id', $shopId)->where('status', '1')->sum('amount');
    }
}

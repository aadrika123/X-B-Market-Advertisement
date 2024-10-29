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
        return self::select('id', 'financial_year', DB::raw('ROUND(amount, 2) as amount'),'payment_status', DB::raw("TO_CHAR(payment_date, 'DD-MM-YYYY') as payment_date"), 'tran_id')->where('shop_id', $shopId)->orderBy('financial_year', 'ASC')->get();
    }

    /**
     * | Total Demand Generated shop category wise
     */
    public function totalDemand($shopType)
    {
        return MarShopDemand::select('*')->where('shop_category_id', $shopType)->where('status', '1')->sum('amount');
    }

    /**
     * | Total Arrear Demand Generated shop category wise
     */
    public function totalArrearDemand($shopType, $currentYear)
    {
        return MarShopDemand::select('*')->where('shop_category_id', $shopType)->where('status', '1')->where('financial_year', '<', $currentYear)->sum('amount');
    }


    /**
     * | Total Current Demand Generated shop category wise
     */
    public function totalCurrentDemand($shopType, $currentYear)
    {
        return MarShopDemand::select('*')->where('shop_category_id', $shopType)->where('status', '1')->where('financial_year', '=', $currentYear)->sum('amount');
    }

    /**
     * | Get Shop All Generated Demands For DCB Reports
     */
    public function shopDemand($shopId)
    {
        return MarShopDemand::select('*')->where('shop_id', $shopId)->where('status', '1')->sum('amount');
    }

    /**
     * | Get Generated Demand Details Pay Before
     */
    public function payBeforeDemand($shopId, $financialYear)
    {
        return self::select('financial_year', 'amount')->where('shop_id', $shopId)->where('financial_year', '=', $financialYear)->where('payment_status', '0')->orderBy('financial_year', 'ASC')->get();
    }
    /**
     * | Get Generated Demand Details Pay Before
     */
    public function payBeforeDemandv1($shopId,)
    {
        return self::select('financial_year', 'amount')->where('shop_id', $shopId)->where('payment_status', '0')->where('amount', '<>',null)->orderBy('financial_year', 'ASC')->get();
    }

    /**
     * | Get Generated Demand Details Pay Before
     */
    public function payBeforeAllDemand($shopId)
    {
        return self::select('financial_year', DB::raw('ROUND(amount, 2) as amount'))
            ->where('shop_id', $shopId)
            ->where('payment_status', '0')
            ->orderBy('financial_year', 'ASC')
            ->get();
    }

    # get consumer demand details 
    public function CheckConsumerDemand($req)
    {
        return self::where('shop_id', $req->shopId)
            ->where('status', true)
            ->orderByDesc('id');
    }
}

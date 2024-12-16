<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopTcVisit extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'shop_tc_visits';

    public function addTcVisitRecord($req)
    {
        $shopTcVisit = new ShopTcVisit();
        $shopTcVisit->shop_id           = $req->shopId ?? null;
        $shopTcVisit->zone_id           = $req->zoneId ?? null;
        $shopTcVisit->zone              = $req->circleName ?? null;
        $shopTcVisit->amc_shop_no       = $req->amcShopNo ?? null;
        $shopTcVisit->shop_type         = $req->shopType ?? null;
        $shopTcVisit->market_name       = $req->marketName ?? null;
        $shopTcVisit->shop_category     = $req->shopCategoryName ?? null;
        $shopTcVisit->allottee          = $req->alloteeName ?? null;
        $shopTcVisit->shop_owner_name   = $req->shopOwnerName ?? null;
        $shopTcVisit->arrear_demands    = $req->arrearDemand ?? null;
        $shopTcVisit->current_demands   = $req->currentDemands ?? null;
        $shopTcVisit->total_demands     = $req->totalDemand ?? null;
        $shopTcVisit->citizen_remark    = $req->citizenRemark ?? null;
        $shopTcVisit->tc_remark         = $req->tcRemark ?? null;
        $shopTcVisit->location          = $req->location ?? null;
        $shopTcVisit->latitude          = $req->lat ?? null;
        $shopTcVisit->longitude         = $req->lan ?? null;
        $shopTcVisit->emp_details_id    = $req->empDetailId ?? null;
        $shopTcVisit->report_type       = $req->reportType ?? null;
        $shopTcVisit->mobile_no          = $req->mobile_no ?? null;

        $shopTcVisit->save();

        return $shopTcVisit;
    }
    public function getDetailsRecords($request)
    {
        return self::select(
            'shop_tc_visits.*'
        )
            // ->leftjoin('m_circle', 'm_circle.id', 'shop_tc_visits.zone_id')
            ->where('shop_tc_visits.id', $request->applicationId);
    }
}

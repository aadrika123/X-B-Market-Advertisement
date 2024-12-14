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
        $shopTcVisit->zone_id           = $req->zoneId ?? null;
        $shopTcVisit->shop_id           = $req->shopId ?? null;
        $shopTcVisit->amc_shop_no       = $req->amcShopNo ?? null;
        $shopTcVisit->shop_type         = $req->shopType ?? null;
        $shopTcVisit->market_name       = $req->marketName ?? null;
        $shopTcVisit->allottee          = $req->allottee ?? null;
        $shopTcVisit->shop_owner_name   = $req->shopOwnerName ?? null;
        $shopTcVisit->arrear_demands    = $req->arrearDemands ?? null;
        $shopTcVisit->current_demands   = $req->currentDemands ?? null;
        $shopTcVisit->total_demands     = $req->totalDemands ?? null;
        $shopTcVisit->citizen_remark    = $req->citizenRemark ?? null;
        $shopTcVisit->tc_remark         = $req->tcRemark ?? null;
        $shopTcVisit->location          = $req->location ?? null;
        $shopTcVisit->latitude          = $req->latitude ?? null;
        $shopTcVisit->longitude         = $req->longitude ?? null;
        $shopTcVisit->emp_details_id     = $req->empDetailId ?? null;
        $shopTcVisit->report_type       = $req->reportType ?? null;

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

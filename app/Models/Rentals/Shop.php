<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Shop extends Model
{
  use HasFactory;
  protected $guarded = [];
  protected $table = 'mar_shops';

  public function getGroupById($id)
  {
    return Shop::select(
      '*',
      DB::raw("
          CASE 
          WHEN status = '0' THEN 'Deactivated'  
          WHEN status = '1' THEN 'Active'
        END as status,
        TO_CHAR(created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(created_at,'HH12:MI:SS AM') as time
          ")
    )
      ->where('id', $id)
      ->first();
  }

  /**
   * | Get All Shop list
   */
  public function retrieveAll()
  {
    return Shop::select(
      'mar_shops.*',
      'mst.shop_type',
      DB::raw("
          CASE 
          WHEN mar_shops.status = '0' THEN 'Deactivated'  
          WHEN mar_shops.status = '1' THEN 'Active'
        END as status,
        TO_CHAR(mar_shops.created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(mar_shops.created_at,'HH12:MI:SS AM') as time
          ")
    )
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->orderBy('mar_shops.id', 'desc')
      ->get();
  }

  /**
   * | Lis Of All Active Shop
   */
  public function retrieveActive()
  {
    return Shop::select(
      'mar_shops.*',
      'mst.shop_type',
      DB::raw("
          CASE 
          WHEN mar_shops.status = '0' THEN 'Deactivated'  
          WHEN mar_shops.status = '1' THEN 'Active'
        END as status,
        TO_CHAR(mar_shops.created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(mar_shops.created_at,'HH12:MI:SS AM') as time
          ")
    )
      ->where('mar_shops.status', 1)
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->orderBy('mar_shops.id', 'desc')
      ->get();
  }

  /**
   * | Get All Shop List By Ulb Id 
   */
  public function getAllShopUlbWise($ulbId, $currentFyear)
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'mst.shop_type',
      DB::raw("COALESCE(SUM(CASE WHEN mar_shop_demands.financial_year < '$currentFyear' AND mar_shop_demands.payment_status = 0 THEN mar_shop_demands.amount ELSE 0 END), 0) AS arrear_demand"),
      DB::raw("COALESCE(SUM(CASE WHEN mar_shop_demands.financial_year >= '$currentFyear' AND mar_shop_demands.payment_status = 0 THEN mar_shop_demands.amount ELSE 0 END), 0) AS current_demand")
    )
      ->leftJoin('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->leftJoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftJoin('mar_shop_demands', 'mar_shops.id', '=', 'mar_shop_demands.shop_id') // Join properly to avoid Cartesian product
      ->where('mar_shops.ulb_id', $ulbId)
      ->groupBy('mar_shops.id', 'mc.circle_name', 'mm.market_name', 'mst.shop_type') // Group by necessary columns to aggregate data
      ->orderByDesc('mar_shops.id');
  }
  /**
   * | Get All Shop List By Ulb Id 
   */
  public function getShopDmeand($shopId, $currentFyear)
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'mst.shop_type',
      DB::raw("COALESCE(SUM(CASE WHEN mar_shop_demands.financial_year < '$currentFyear' AND mar_shop_demands.payment_status = 0 THEN mar_shop_demands.amount ELSE 0 END), 0) AS arrear_demand"),
      DB::raw("COALESCE(SUM(CASE WHEN mar_shop_demands.financial_year >= '$currentFyear' AND mar_shop_demands.payment_status = 0 THEN mar_shop_demands.amount ELSE 0 END), 0) AS current_demand")
    )
      ->leftJoin('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->leftJoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftJoin('mar_shop_demands', 'mar_shops.id', '=', 'mar_shop_demands.shop_id') // Join properly to avoid Cartesian product
      ->where('mar_shops.id', $shopId)
      ->groupBy('mar_shops.id', 'mc.circle_name', 'mm.market_name', 'mst.shop_type') // Group by necessary columns to aggregate data
      ->orderByDesc('mar_shops.id');
  }


  /**
   * | Get Shop List By Market Id
   */
  public function getShop($marketid)
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'mst.shop_type',
      'msp.amount as last_payment_amount'
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      ->where('mar_shops.market_id', $marketid)
      ->where('mar_shops.status', '1');
  }

  /**
   * | Get Shop Details By Market  Id
   */
  public function getShopDetailById($id)
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'sc.construction_type',
      'mst.shop_type',
      'msp.amount as last_payment_amount',
    )
      ->leftjoin('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->join('shop_constructions as sc', 'mar_shops.construction', '=', 'sc.id')
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      ->where('mar_shops.id', $id)
      ->first();
  }

  /**
   * | Get Payment Reciept by Shop Id
   */
  public function getReciept($shopId)
  {
    $shop = Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'sc.construction_type',
      'msp.amount as last_payment_amount',
      'msp.paid_from as payment_from',
      'msp.paid_to as payment_upto',
      'u.name as tcName',
      'u.mobile as tcMobile',
      'u.user_name as tcUserName',
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      ->join('shop_constructions as sc', 'mar_shops.construction', '=', 'sc.id')
      ->join('users as u', 'msp.user_id', '=', 'u.id')
      ->where('mar_shops.id', $shopId)
      ->where('mar_shops.status', '1')
      ->get();
    return $shop;
  }

  /**
   * | Search Shop for Payment
   */
  public function searchShopForPayment($shopCategoryId, $marketId, $currentFyear)
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'sc.construction_type',
      'mst.shop_type',
      'msp.amount as last_payment_amount',
      DB::raw("(SELECT SUM(amount) 
                  FROM mar_shop_demands 
                  WHERE mar_shop_demands.shop_id = mar_shops.id 
                    AND mar_shop_demands.financial_year < '$currentFyear' 
                    AND mar_shop_demands.payment_status = 0) AS arrear"),
      DB::raw("(SELECT SUM(amount) 
                  FROM mar_shop_demands 
                  WHERE mar_shop_demands.shop_id = mar_shops.id 
                    AND mar_shop_demands.financial_year >= '$currentFyear' 
                    AND mar_shop_demands.payment_status = 0) AS current_demand")
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->join('shop_constructions as sc', 'mar_shops.construction', '=', 'sc.id')
      ->leftJoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftJoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      ->where(['mar_shops.shop_category_id' => $shopCategoryId, 'mar_shops.market_id' => $marketId])
      ->where('mar_shops.status', 1)
      ->orderByDesc('mar_shops.id');
  }

  /**
   * | Search Shop for Payment
   */
  public function searchShopForPaymentv1($key, $refNo, $currentFyear)
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'sc.construction_type',
      'mst.shop_type',
      'msp.amount as last_payment_amount',
      DB::raw("(SELECT SUM(amount) 
                  FROM mar_shop_demands 
                  WHERE mar_shop_demands.shop_id = mar_shops.id 
                    AND mar_shop_demands.financial_year < '$currentFyear' 
                    AND mar_shop_demands.payment_status = 0) AS arrear"),
      // DB::raw('case when mar_shops.last_tran_id is NULL then 0 else 1 end as shop_payment_status')
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->join('shop_constructions as sc', 'mar_shops.construction', '=', 'sc.id')
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      // ->where(['mar_shops.shop_category_id' => $shopCategoryId, 'mar_shops.circle_id' => $circleId, 'mar_shops.market_id' => $marketId]
      ->where('mar_shops.' . $key, 'ILIKE', '%' . $refNo . '%')
      ->where('mar_shops.status', 1)
      ->orderByDesc('mar_shops.id');
    // ->get();
  }
  /**
   * | Search Shop for Payment
   */
  public function searchShopForPaymentv2($key, $refNo, $currentFyear)
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'sc.construction_type',
      'mst.shop_type',
      'msp.amount as last_payment_amount',
      DB::raw("(SELECT SUM(amount) 
      FROM mar_shop_demands 
      WHERE mar_shop_demands.shop_id = mar_shops.id 
        AND mar_shop_demands.financial_year < '$currentFyear' 
        AND mar_shop_demands.payment_status = 0) AS arrear"),
      // DB::raw('case when mar_shops.last_tran_id is NULL then 0 else 1 end as shop_payment_status')
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->join('shop_constructions as sc', 'mar_shops.construction', '=', 'sc.id')
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      // ->where(['mar_shops.shop_category_id' => $shopCategoryId, 'mar_shops.circle_id' => $circleId, 'mar_shops.market_id' => $marketId]
      ->where('mar_shops.' . $key, $refNo)
      ->where('status', 1)
      ->orderByDesc('mar_shops.id');
    // ->get();
  }

  /**
   * | Count Total No of Shop (Shop Type Wise)
   */
  public function totalShop($shopType)
  {
    return Shop::where('shop_category_id', $shopType)->count();
  }

  /**
   * | Get Shopwise DCB
   */
  public function shopwiseDcb()
  {
    return Shop::select(
      'mar_shops.id',
      'mar_shops.shop_no',
      'mar_shops.shop_category_id',
      'mar_shops.allottee',
      'mar_shops.contact_no',
      'mc.circle_name',
      'mm.market_name',
      'mst.shop_type',
    )
      ->leftjoin('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->join("mar_shop_types as mst", "mst.id", "mar_shops.shop_category_id");
  }
  /**
   * | Get ShopList By Mobile No
   */
  public function searchShopByContactNo($mobileNo)
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'mst.shop_type',
      'msp.amount as last_payment_amount'
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      ->where('mar_shops.contact_no', $mobileNo)
      ->where('mar_shops.status', '1');
  }
  /**
   * | Get ShopList 
   */
  public function getShopData()
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'mst.shop_type',
      'msp.amount as last_payment_amount'
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      ->where('mar_shops.status', '1');
    // ->where(function ($query) use ($value) {
    //   $query->orwhere('shop_no', 'ILIKE', '%' . $value . '%')
    //     ->orwhere("allottee", 'ILIKE', '%' . $value . '%')
    //     ->orwhere("contact_no", 'ILIKE', '%' . $value . '%');
    // });
  }
}

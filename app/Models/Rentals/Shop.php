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


  public function getAllShopUlbWise($ulbId)
  {
    // return Shop::all();
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'mst.shop_type',
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->orderByDesc('id')
      ->where('mar_shops.ulb_id', $ulbId);
    // ->where('mar_shops.status', '1');
    // ->get();
  }

  public function getShop($marketid)
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'mst.shop_type',
      // 'msp.payment_date as last_payment_date',
      // DB::raw("TO_CHAR(msp.payment_date, 'DD-MM-YYYY') as last_payment_date"),
      'msp.amount as last_payment_amount'
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      ->where('mar_shops.market_id', $marketid)
      ->where('mar_shops.status', '1');
    // ->get();
  }

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
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->join('shop_constructions as sc', 'mar_shops.construction', '=', 'sc.id')
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      ->where('mar_shops.id', $id)
      ->first();
  }

  public function getReciept($shopId)
  {
    $shop = Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'sc.construction_type',
      // 'msp.payment_date as last_payment_date',
      // DB::raw("TO_CHAR(msp.payment_date, 'DD-MM-YYYY') as last_payment_date"),
      'msp.amount as last_payment_amount',
      'msp.paid_from as payment_from',
      'msp.paid_to as payment_upto',
      'u.name as tcName',
      'u.mobile as tcMobile',
      'u.user_name as tcUserName',
      // 'mst.shop_type',
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      // ->join('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
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
  public function searchShopForPayment($shopCategoryId, $circleId, $marketId)
  {
    return Shop::select(
      'mar_shops.*',
      'mc.circle_name',
      'mm.market_name',
      'sc.construction_type',
      'mst.shop_type',
      // DB::raw("TO_CHAR(msp.payment_date, 'DD-MM-YYYY') as last_payment_date"),
      'msp.amount as last_payment_amount',
      // DB::raw("TO_CHAR(msp.paid_to, 'DD-MM-YYYY') as payment_upto")
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      ->join('shop_constructions as sc', 'mar_shops.construction', '=', 'sc.id')
      ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      ->where(['mar_shops.shop_category_id' => $shopCategoryId, 'mar_shops.circle_id' => $circleId, 'mar_shops.market_id' => $marketId])
      ->orderByDesc('mar_shops.id')
      ->get();
  }

  /**
   * | Count Total No of Shop (Shop Type Wise)
   */
  public function totalShop($shopType)
  {
    return Shop::where('shop_category_id', $shopType)->count();
  }

  /**
   * | Get Shop Details By ID FOr DCB Reports
   */
  public function getShopDetailByIdForDCB($id)
  {
    return Shop::select(
      'mar_shops.shop_no',
      'mar_shops.allottee',
      'mar_shops.contact_no',
      'mc.circle_name',
      'mm.market_name',
      // 'sc.construction_type',
      // 'mst.shop_type',
      // 'msp.amount as last_payment_amount',
    )
      ->join('m_circle as mc', 'mar_shops.circle_id', '=', 'mc.id')
      ->join('m_market as mm', 'mar_shops.market_id', '=', 'mm.id')
      // ->join('shop_constructions as sc', 'mar_shops.construction', '=', 'sc.id')
      // ->leftjoin('mar_shop_types as mst', 'mar_shops.shop_category_id', '=', 'mst.id')
      // ->leftjoin('mar_shop_payments as msp', 'mar_shops.last_tran_id', '=', 'msp.id')
      ->where('mar_shops.id', $id)
      ->first();
  }
}

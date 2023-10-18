<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarToll extends Model
{
    use HasFactory;
    protected $guarded = [];

    // public function retrieveAll()
    // {
    //     return MarToll::select(
    //         '*',
    //         DB::raw("
    //     CASE 
    //     WHEN status = '0' THEN 'Deactivated'  
    //     WHEN status = '1' THEN 'Active'
    //   END as status,
    //   TO_CHAR(created_at::date,'dd-mm-yyyy') as date,
    //   TO_CHAR(created_at,'HH12:MI:SS AM') as time
    //     ")
    //     )
    //         ->orderBy('id', 'desc');
    //     // ->get();
    // }

    /**
     * | List All Active Toll 
     */
    public function retrieveActive()
    {
        return MarToll::select(
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
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();
    }


    // public function getTollById($id)
    // {
    //     return MarToll::select(
    //         '*',
    //         DB::raw("
    //     CASE 
    //     WHEN status = '0' THEN 'Deactivated'  
    //     WHEN status = '1' THEN 'Active'
    //   END as status,
    //   TO_CHAR(created_at::date,'dd-mm-yyyy') as date,
    //   TO_CHAR(created_at,'HH12:MI:SS AM') as time
    //     ")
    //     )
    //         ->where('id', $id)
    //         ->first();
    // }

    /**
     * | Get Ulb Wise Toll list
     */

    /**
     * | Get All Toll ULB wise
     */
    public function getUlbWiseToll($ulbId)
    {
        return MarToll::select(
            'mar_tolls.*',
            'mc.circle_name',
            'mm.market_name',
            'mtp.to_date as payment_upto'
        )
            ->join('m_circle as mc', 'mar_tolls.circle_id', '=', 'mc.id')
            ->join('m_market as mm', 'mar_tolls.market_id', '=', 'mm.id')
            ->leftjoin('mar_toll_payments as mtp', 'mar_tolls.last_tran_id', '=', 'mtp.id')
            ->where('mar_tolls.ulb_id', $ulbId)
            ->where('mar_tolls.status', '1');
    }

    /**
     * | Get All Toll By Market Id 
     */
    public function getToll($marketid)
    {
        return MarToll::select(
            'mar_tolls.*',
            'mc.circle_name',
            'mm.market_name',
            DB::raw("TO_CHAR(msp.payment_date, 'DD-MM-YYYY') as last_payment_date"),
            'msp.amount as last_payment_amount'
        )
            ->join('m_circle as mc', 'mar_tolls.circle_id', '=', 'mc.id')
            ->join('m_market as mm', 'mar_tolls.market_id', '=', 'mm.id')
            ->leftjoin('mar_shop_payments as msp', 'mar_tolls.last_tran_id', '=', 'msp.id')
            ->where('mar_tolls.market_id', $marketid)
            ->where('mar_tolls.status', '1');
    }

    /**
     * | Get toll Details By Id
     */
    public function getTollDetailById($id)
    {
        return MarToll::select(
            'mar_tolls.*',
            'mc.circle_name',
            'mm.market_name',
            DB::raw("TO_CHAR(mar_tolls.last_payment_date, 'DD-MM-YYYY') as last_payment_date"),
            'mar_tolls.last_amount as last_payment_amount',
            DB::raw("TO_CHAR(mtp.to_date, 'DD-MM-YYYY') as payment_upto"),
        )
            ->join('m_circle as mc', 'mar_tolls.circle_id', '=', 'mc.id')
            ->join('m_market as mm', 'mar_tolls.market_id', '=', 'mm.id')
            ->leftjoin('mar_toll_payments as mtp', 'mar_tolls.last_tran_id', '=', 'mtp.id')
            ->where('mar_tolls.id', $id)
            ->first();
    }

    /**
     * | Get Toll Reciept By Toll Id 
     */
    public function getReciept($id)
    {
        return MarToll::select(
            'mar_tolls.toll_no',
            'mar_tolls.vendor_name',
            'mar_tolls.address',
            'mar_tolls.rate',
            DB::raw("TO_CHAR(mar_tolls.last_payment_date, 'DD-MM-YYYY') as last_payment_date"),
            DB::raw("TO_CHAR(mar_tolls.created_at, 'DD-MM-YYYY') as toll_added_date"),
            'mar_tolls.last_amount',
            'mar_tolls.mobile',
            'mar_tolls.vendor_type',
            'mc.circle_name',
            'mm.market_name',
            'u.name as tcName',
            'u.mobile as tcMobile',
            'u.user_name as tcUserName',
            'ulb.ulb_name as ulbName',
            'ulb.logo',
            'ulb.toll_free_no',
            'ulb.current_website as website',
            DB::raw("TO_CHAR(mtp.from_date, 'DD-MM-YYYY') as dateFrom"),
            DB::raw("TO_CHAR(mtp.to_date, 'DD-MM-YYYY') as dateTo"),
            'mtp.transaction_no',
            'mtp.session',
        )
            ->join('m_circle as mc', 'mar_tolls.circle_id', '=', 'mc.id')
            ->join('m_market as mm', 'mar_tolls.market_id', '=', 'mm.id')
            ->join('users as u', 'mar_tolls.user_id', '=', 'u.id')
            ->join('ulb_masters as ulb', 'mar_tolls.ulb_id', '=', 'ulb.id')
            ->leftjoin('mar_toll_payments as mtp', 'mar_tolls.last_tran_id', '=', 'mtp.id')
            ->where('mar_tolls.id', $id)
            ->first();
    }
}

<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarToll extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function retrieveAll()
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
            ->orderBy('id', 'desc');
            // ->get();
    }

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

    public function getTollById($id)
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
            ->where('id', $id)
            ->first();
    }

    /**
     * | Get Ulb Wise Toll list
     */
    public function getUlbWiseToll($ulbId)
    {
        return MarToll::select(
            'mar_tolls.*',
            'mc.circle_name',
            'mm.market_name',
            // 'msp.payment_date as last_payment_date',
            DB::raw("TO_CHAR(msp.payment_date, 'DD-MM-YYYY') as last_payment_date"),
            'msp.amount as last_payment_amount'
        )
            ->join('m_circle as mc', 'mar_tolls.circle_id', '=', 'mc.id')
            ->join('m_market as mm', 'mar_tolls.market_id', '=', 'mm.id')
            ->leftjoin('mar_shop_payments as msp', 'mar_tolls.last_tran_id', '=', 'msp.id')
            ->where('mar_tolls.ulb_id', $ulbId)
            ->where('mar_tolls.status', '1');
        // ->get();
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
            // 'msp.payment_date as last_payment_date',
            DB::raw("TO_CHAR(msp.payment_date, 'DD-MM-YYYY') as last_payment_date"),
            'msp.amount as last_payment_amount'
        )
            ->join('m_circle as mc', 'mar_tolls.circle_id', '=', 'mc.id')
            ->join('m_market as mm', 'mar_tolls.market_id', '=', 'mm.id')
            ->leftjoin('mar_shop_payments as msp', 'mar_tolls.last_tran_id', '=', 'msp.id')
            ->where('mar_tolls.market_id', $marketid)
            ->where('mar_tolls.status', '1');
        // ->get();
    }

    public function getTallDetailById($id)
    {
      return MarToll::select(
        'mar_tolls.*',
        'mc.circle_name',
        'mm.market_name',
        DB::raw("TO_CHAR(mar_tolls.last_payment_date, 'DD-MM-YYYY') as last_payment_date"),
        'mar_tolls.last_amount as last_payment_amount',      
        // DB::raw("TO_CHAR(msp.payment_date, 'DD-MM-YYYY') as last_payment_date"),
        // 'msp.amount as last_payment_amount'
        // DB::raw("select payment_date from mar_shop_payments where shop_id=$id limit 1 as last_payment_date")
        // DB::raw("select payment_date from mar_shop_payments where shop_id=$id ORDER BY id DESC LIMIT 1"), 
      )
        // ->join('mar_shop_payments','mar_shop_payments.shop_id')
        ->join('m_circle as mc', 'mar_tolls.circle_id', '=', 'mc.id')
        ->join('m_market as mm', 'mar_tolls.market_id', '=', 'mm.id')
        // ->leftjoin('mar_shop_payments as msp', 'mar_tolls.last_tran_id', '=', 'msp.id')
        ->where('mar_tolls.id', $id)
        ->first();
    }
}

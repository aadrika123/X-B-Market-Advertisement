<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdTranDetail extends Model
{
    use HasFactory;

    /**
     * | Save the trans details  
     */
    public function saveTransDetails($tranId, $refReq)
    {
        $mPetTranDetail = new AdTranDetail();
        $mPetTranDetail->tran_id        = $tranId;
        $mPetTranDetail->application_id = $refReq['id'];
        $mPetTranDetail->charge_id      = $refReq['refChargeId'];
        // $mPetTranDetail->total_demand   = $refReq['roundAmount'];
        $mPetTranDetail->payment_for    = $refReq['tranTypeId'];
        $mPetTranDetail->save();
    }
}

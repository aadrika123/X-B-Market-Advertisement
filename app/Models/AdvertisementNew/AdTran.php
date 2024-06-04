<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class AdTran extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Save the transaction details 
     */
    public function saveTranDetails($req)
    {
        $paymentMode = Config::get("rig.PAYMENT_MODE");

        $mPetTran = new AdTran();
        $mPetTran->related_id   = $req['id'];
        $mPetTran->ward_id      = $req['wardId'];
        $mPetTran->ulb_id       = $req['ulbId'];
        $mPetTran->tran_date    = $req['todayDate'];
        $mPetTran->tran_no      = $req['tranNo'];
        $mPetTran->payment_mode = $req['paymentMode'];
        $mPetTran->tran_type    = $req['tranType'];
        $mPetTran->amount       = $req['amount'];
        $mPetTran->emp_dtl_id   = $req['empId'] ?? null;
        $mPetTran->ip_address   = $req['ip'] ?? null;
        $mPetTran->user_type    = $req['userType'];
        $mPetTran->is_jsk       = $req['isJsk'] ?? false;
        $mPetTran->citizen_id   = $req['citId'] ?? null;
        $mPetTran->tran_type_id = $req['tranTypeId'];
        $mPetTran->round_amount = $req['roundAmount'];
        $mPetTran->token_no     = $req['tokenNo'];

        # For online payment
        if ($req['paymentMode'] == $paymentMode['1']) {
            $mPetTran->pg_response_id = $req['pgResponseId'];                               // Online response id
            $mPetTran->pg_id = $req['pgId'];                                                // Payment gateway id
        }
        $mPetTran->save();

        return [
            'transactionNo' => $req['tranNo'],
            'transactionId' => $mPetTran->id
        ];
    }

    /**
     * | Update request for transaction table
     */
    public function saveStatusInTrans($id, $refReq)
    {
        AdTran::where('id', $id)
            ->update($refReq);
    }

    /**
     * | Get transaction details accoring to related Id and transaction type
     */
    public function getTranDetails($relatedId)
    {
        return AdTran::where('related_id', $relatedId)
            // ->where('tran_type_id', $tranType)
            ->where('status', 1)
            ->orderByDesc('id');
    }
}

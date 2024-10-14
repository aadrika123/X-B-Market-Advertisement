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
        $paymentMode = Config::get("advert.PAYMENT_MODE");

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

    /**
     * | Get transaction by application No
     */
    public function getTranByApplicationId($applicationId)
    {
        return AdTran::where('related_id', $applicationId)
            ->where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | Get transaction details according to transaction no
     */
    public function getTranDetailsByTranNo($tranNo)
    {
        return AdTran::select(
            'ad_trans.id AS refTransId',
            'ad_trans.*',
            'ulb_masters.ulb_name',
            'ulb_masters.email',
            'ulb_masters.address'
        )
            ->where('ad_trans.tran_no', $tranNo)
            ->where('ad_trans.status', 1)
            ->join('ulb_masters', 'ulb_masters.id', 'ad_trans.ulb_id')
            ->orderByDesc('ad_trans.id');
    }
    public function Tran($fromDate, $toDate)
    {
        return AdTran::select(
            'ad_trans.tran_no', 'ad_trans.tran_date','ad_trans.payment_mode','ad_trans.tran_type',
            'agency_hoarding_approve_applications.from_date','agency_hoarding_approve_applications.to_date','ad_trans.amount'
            )
            ->join('agency_hoarding_approve_applications','agency_hoarding_approve_applications.id','=','ad_trans.related_id')
            ->where('ad_trans.status', 1)
            ->where('ad_trans.tran_date', '>=', $fromDate)
            ->where('ad_trans.tran_date', '<=', $toDate)
            ->orderByDesc('ad_trans.id');
    }
}

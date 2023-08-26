<?php

namespace App\Models\Pet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetRazorPayRequest extends Model
{
    use HasFactory;

    /**
     * | Get details for checking the payment
     */
    public function getRazorpayRequest($req)
    {
        return PetRazorPayRequest::where("order_id", $req->orderId)
            ->where("related_id", $req->id)
            ->where("status", 2)
            ->first();
    }

    /**
     * | Save the razorpay request data
     */
    public function savePetRazorpayReq($applicationId, $paymentDetails, $jsonIncodedData)
    {
        $RazorPayRequest = new PetRazorPayRequest();
        $RazorPayRequest->related_id        = $applicationId;
        $RazorPayRequest->payment_from      = $paymentDetails->chargeCategory;
        $RazorPayRequest->amount            = $paymentDetails->amount;
        $RazorPayRequest->demand_id         = $paymentDetails->chargeId;
        $RazorPayRequest->ip_address        = $paymentDetails->ip;
        $RazorPayRequest->order_id          = $paymentDetails->orderId;
        $RazorPayRequest->department_id     = $paymentDetails->departmentId;
        $RazorPayRequest->note              = $jsonIncodedData;
        $RazorPayRequest->round_amount      = $paymentDetails->regAmount;
        $RazorPayRequest->save();
    }
}

<?php

namespace App\Models\Pet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetRazorPayResponse extends Model
{
    use HasFactory;

    /**
     * | Save data for the razorpay response
     */
    public function savePaymentResponse($RazorPayRequest, $webhookData)
    {
        $RazorPayResponse = new PetRazorPayResponse();
        $RazorPayResponse->related_id   = $RazorPayRequest->related_id;
        $RazorPayResponse->request_id   = $RazorPayRequest->id;
        $RazorPayResponse->amount       = $webhookData->amount;
        $RazorPayResponse->merchant_id  = $webhookData->merchantId ?? null;
        $RazorPayResponse->order_id     = $webhookData->orderId;
        $RazorPayResponse->payment_id   = $webhookData->paymentId;
        $RazorPayResponse->save();
        return [
            'razorpayResponseId' => $RazorPayResponse->id
        ];
    }
}

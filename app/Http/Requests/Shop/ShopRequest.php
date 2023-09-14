<?php

namespace App\Http\Requests\Shop;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class ShopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'circleId'                 =>   'required|integer',
            'marketId'                 =>   'required|string',
            // 'lastPaymentDate'          =>   'required|date_format:Y-m-d|after_or_equal:'.date('Y-m-d'),
            // 'lastPaymentAmount'        =>   'required|numeric',
            'allottee'                 =>   'required|regex:/^[A-Za-z ]+$/',
            // 'shopNo'                   =>   'required|regex:/^[A-Za-z0-9 ]+$/',
            'address'                  =>   'nullable|regex:/^[A-Za-z0-9, ]+$/',
            // 'rate'                     =>   'required|numeric',
            // 'arrear'                   =>   'required|numeric',
            'allottedLength'           =>   'required|numeric',
            'allottedBreadth'          =>   'required|numeric',
            'allottedHeight'           =>   'required|numeric',
            // 'area'                     =>   'nullable|numeric',
            'presentLength'            =>   'nullable|numeric',
            'presentBreadth'           =>   'nullable|numeric',
            'presentHeight'            =>   'nullable|numeric',
            'noOfFloors'               =>   'nullable|string',
            'presentOccupier'          =>   'nullable|regex:/^[A-Za-z ]+$/',
            'tradeLicense'             =>   'nullable|string',
            'construction'             =>   'required|integer',
            'electricity'              =>   'required|string',
            'water'                    =>   'required|string',
            'salePurchase'             =>   'nullable|string',
            'contactNo'                =>   'required|numeric|digits:10',
            // 'longitude'                =>   'nullable|string',
            // 'latitude'                 =>   'nullable|string',
            'photo1Path'               =>   'nullable|image|mimes:jpg,jpeg,png',
            'photo2Path'               =>   'nullable|image|mimes:jpg,jpeg,png',
            // 'remarks'                  =>   'nullable|string',
            'lastTranId'               =>   'nullable|numeric',
            'shopCategoryId'           =>   'required|numeric',
            'rate'                     =>   $this->shopCategoryId==3?'required|numeric':'nullable|numeric',

        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ], 200),);
    }
}

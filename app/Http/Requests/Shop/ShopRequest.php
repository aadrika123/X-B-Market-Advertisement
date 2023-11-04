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
            'allottee'                 =>   'required|regex:/^[A-Za-z ]+$/',
            'address'                  =>   'nullable|regex:/^[A-Za-z0-9, ]+$/',
            'allottedLength'           =>   'required|numeric',
            'allottedBreadth'          =>   'required|numeric',
            'allottedHeight'           =>   'required|numeric',
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
            'photo1Path'               =>   'nullable|image|mimes:jpg,jpeg,png',
            'photo2Path'               =>   'nullable|image|mimes:jpg,jpeg,png',
            'attotedUpto'              =>   'nullable|date_format:Y-m-d',
            'shopType'                 =>   'nullable|string',
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

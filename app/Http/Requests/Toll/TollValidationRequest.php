<?php

namespace App\Http\Requests\Toll;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TollValidationRequest extends FormRequest
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
            'circleId'              =>   'required|integer',
            'vendorName'            =>   'required|regex:/^[A-Za-z ]+$/',
            'address'               =>   'required|string|max:255',
            'rate'                  =>   'required|numeric',
            'marketId'              =>   'required|integer',
            'mobile'                =>   'required|digits:10',
            'remarks'               =>   'nullable|string',
            'photograph1'           =>   'required|image|mimes:jpeg,png,jpg',
            'vendorType'            =>   'required|in:schedule,unscheduled'
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

<?php

namespace App\Http\Requests\AgencyNew;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class AddHoardingRequest extends FormRequest
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
            'agencyId'             => 'required|integer|min:1',
            'hoardingId'           => 'nullable|integer|min:1',
            'hoardingType'         => 'nullable|string',
            'allotmentDate'        => 'nullable|date',
            'advertiser'           => 'required|string',
            'temporaryHoardingType' => 'nullable|string',
            'hoardingSize'         => 'nullable|string',
            'from'                 => 'nullable|date',
            'to'                   => 'nullable|date',
            'rate'                 => 'nullable|numeric',
            'email'                => 'nullable|email',
            'residenceAddress'     => 'nullable|string',
            'purpose'              => 'nullable|string',
            'workflowId'           => 'nullable|integer|min:1',
            'applicationType'      => 'required|string|in:PERMANANT,TEMPORARY',
            'squareFeetId'         => 'nullable|integer|min:1',
            'hoardingType'          => 'nullable|string',
            'Noofhoardings'        =>  'nullable',
            'mobileNo'              => 'required|string|regex:/^[0-9]{10}$/',
            'documents'            => 'nullable|array',
            'documents.*.image'    => 'nullable|mimes:png,jpeg,pdf,jpg',
            'documents.*.docCode'  => 'nullable|string',
            'documents.*.ownerDtlId' => 'nullable|integer',
            'addressField'           => 'required|array',
            'addressField.*.address' => 'required|string'
        ];
    }
    /**
     * | Error Message
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ], 422),);
    }
}

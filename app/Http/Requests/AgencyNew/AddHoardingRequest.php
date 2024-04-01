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
            'workflowId'           => 'nullable|integer|min:1',
            'applicationType'      => 'required|string|in:PERMANENT,TEMPORARY', // Assuming these are the valid application types
            'squareFeetId'         => 'nullable|integer|min:1',
            'hoardingType'          => 'nullable|string',
            'documents'            => 'required|array',
            'documents.*.image'    => 'required|mimes:png,jpeg,pdf,jpg',
            'documents.*.docCode'  => 'required|string',
            'documents.*.ownerDtlId' => 'nullable|integer'
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

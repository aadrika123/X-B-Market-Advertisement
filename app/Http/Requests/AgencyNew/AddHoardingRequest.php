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
            'agencyId'             => 'required',
            'hoardingId'           => 'nullable',
            'hoardingType'         => 'nullable',
            'allotmentDate'        => 'nullable',
            'advertiser'           => 'required',
            'temporaryHoardingType' => 'nullable',
            'hoardingSize'         => 'nullable',
            'from'                 => 'nullable',
            'to'                   => 'nullable',
            'rate'                 => 'nullable',
            'fatherName'           => 'nullable',
            'email'                => 'nullable',
            'residenceAddress'     => 'nullable',
            'workflowId'           => 'nullable',
            'applicationType'      => 'required',
            'squareFeetId'         =>  'nullable',
            'hoardingType'          => 'nullable',
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

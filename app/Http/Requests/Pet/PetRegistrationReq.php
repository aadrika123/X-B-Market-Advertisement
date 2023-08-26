<?php

namespace App\Http\Requests\Pet;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PetRegistrationReq extends FormRequest
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
        $rules = [
            'address'               => 'required|',
            'applyThrough'          => 'required|int|in:1,2',
            'breed'                 => 'required|',
            'ownerCategory'         => 'required|in:1,2',
            'color'                 => 'required|',
            'dateOfLepVaccine'      => 'required|date|date_format:Y-m-d',
            'dateOfRabies'          => 'required|date|date_format:Y-m-d',
            'doctorName'            => 'required|',
            'doctorRegNo'           => 'required|',
            'petBirthDate'          => 'required|date|date_format:Y-m-d',
            'petFrom'               => 'required|',
            'petGender'             => 'required|int|in:1,2',
            'petIdentity'           => 'required|',
            'petName'               => 'required|',
            'petType'               => 'required|',
            'ulbId'                 => 'required|int',
            'ward'                  => 'required|int',
            'applicantName'         => "required|",
            'mobileNo'              => "required|digits:10|regex:/[0-9]{10}/",
            'email'                 => "nullable|email",
            'panNo'                 => "required|min:10|max:10|alpha_num|",
            'telephone'             => "nullable|int|regex:/[0-9]{10}/",
        ];

        if (isset($this->applyThrough) && $this->applyThrough) {
            $rules['propertyNo'] = 'required|';
        }
        if (isset($this->isRenewal) && $this->isRenewal) {
            $rules['registrationId'] = 'required|int';
            $rules['isRenewal'] = 'int|in:1,0';
        }
        return $rules;
    }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'    => false,
            'message'   => "Validation Error!",
            'error'     => $validator->errors()
        ], 422));
    }
}

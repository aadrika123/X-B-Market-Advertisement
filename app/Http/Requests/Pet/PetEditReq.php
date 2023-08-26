<?php

namespace App\Http\Requests\Pet;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PetEditReq extends FormRequest
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
        $rules['id']                    = 'required|int';
        $rules['breed']                 = 'nullable|';
        $rules['color']                 = 'nullable|';
        $rules['dateOfLepVaccine']      = 'nullable|date|date_format:Y-m-d';
        $rules['dateOfRabies']          = 'nullable|date|date_format:Y-m-d';
        $rules['doctorName']            = 'nullable|';
        $rules['doctorRegNo']           = 'nullable|';
        $rules['petBirthDate']          = 'nullable|date|date_format:Y-m-d';
        $rules['petFrom']               = 'nullable|';
        $rules['petGender']             = 'nullable|int|in:1,2';
        $rules['petIdentity']           = 'nullable|';
        $rules['petName']               = 'nullable|';
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

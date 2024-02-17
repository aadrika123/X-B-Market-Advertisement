<?php

namespace App\Http\Requests\AgencyNew;

use Illuminate\Foundation\Http\FormRequest;

class AddNewAgency extends FormRequest
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
            'agencyName' => 'required|integer',
            'agencyCode' => 'required|string',
            'correspondingAddress' => 'required|string',
            'mobileNo' => 'required|numeric|digits:10',
            'email' => 'nullable|email',
            'contactPerson' => 'nullable',
            'gstNo' => 'nullable|',
            'panNo' => 'nullable|string',
            'profile' => 'nullable|string'
        ];
    }
}

<?php

namespace App\Traits\Marriage;

use Illuminate\Database\Eloquent\Collection;

/**
 * | Trait Created for Gettting Dynamic Saf Details
 */
trait MarriageTrait
{
    /**
     * | Get Basic Details
     */
    public function generateMarriageDetails($data)
    {
        return new Collection([
            ['displayString' => 'Marriage Date', 'key' => 'marriageDate', 'value' => $data->marriage_date],
            ['displayString' => 'Marriage Place', 'key' => 'marriagePlace', 'value' => $data->marriage_place],
            ['displayString' => 'Either of the Bride or Groom belongs to BPL category', 'key' => 'bpl', 'value' => ($data->is_bpl == false) ? 'No' : 'Yes']
        ]);
    }

    /**
     * | Generating Bride Details
     */
    public function generateBrideDetails($data)
    {
        return new Collection([
            ['displayString' => 'Bride Name', 'key' => 'bride_name', 'value' => $data->bride_name,],
            ['displayString' => 'Bride DOB', 'key' => 'bride_dob', 'value' => $data->bride_dob,],
            ['displayString' => 'Bride Age', 'key' => 'bride_age', 'value' => $data->bride_age,],
            ['displayString' => 'Bride Nationality', 'key' => 'bride_nationality', 'value' => $data->bride_nationality,],
            ['displayString' => 'Bride Religion', 'key' => 'bride_religion', 'value' => $data->bride_religion],
            ['displayString' => 'Bride Mobile', 'key' => 'bride_mobile', 'value' => $data->bride_mobile,],
            ['displayString' => 'Bride Aadhar No', 'key' => 'bride_aadhar_no', 'value' => $data->bride_aadhar_no,],
            ['displayString' => 'Bride Email', 'key' => 'bride_email', 'value' => $data->bride_email,],
            ['displayString' => 'Bride Passport No', 'key' => 'bride_passport_no', 'value' => $data->bride_passport_no,],
            ['displayString' => 'Bride Residential Address', 'key' => 'bride_residential_address', 'value' => $data->bride_residential_address,],
            ['displayString' => 'Bride Martial Status', 'key' => 'bride_martial_status', 'value' => $data->bride_martial_status,],
            ['displayString' => 'Bride Father Name', 'key' => 'bride_father_name', 'value' => $data->bride_father_name,],
            ['displayString' => 'Bride Father Aadhar No', 'key' => 'bride_father_aadhar_no', 'value' => $data->bride_father_aadhar_no,],
            ['displayString' => 'Bride Mother Name', 'key' => 'bride_mother_name', 'value' => $data->bride_mother_name,],
            ['displayString' => 'Bride Mother Aadhar No', 'key' => 'bride_mother_aadhar_no', 'value' => $data->bride_mother_aadhar_no,],
            ['displayString' => 'Bride Guardian Name', 'key' => 'bride_guardian_name', 'value' => $data->bride_guardian_name,],
            ['displayString' => 'Bride Guardian Aadhar No', 'key' => 'bride_guardian_aadhar_no', 'value' => $data->bride_guardian_aadhar_no,],
        ]);
    }

    /**
     * | Generating Groom Details
     */
    public function generateGroomDetails($data)
    {
        return new Collection([
            ['displayString' => 'Groom Name', 'key' => 'groom_name', 'value' => $data->groom_name,],
            ['displayString' => 'Groom DOB', 'key' => 'groom_dob', 'value' => $data->groom_dob,],
            ['displayString' => 'Groom Age', 'key' => 'groom_age', 'value' => $data->groom_age,],
            ['displayString' => 'Groom Nationality', 'key' => 'groom_nationality', 'value' => $data->groom_nationality,],
            ['displayString' => 'Groom Religion', 'key' => 'groom_religion', 'value' => $data->groom_religion],
            ['displayString' => 'Groom Mobile', 'key' => 'groom_mobile', 'value' => $data->groom_mobile,],
            ['displayString' => 'Groom Aadhar No', 'key' => 'groom_aadhar_no', 'value' => $data->groom_aadhar_no,],
            ['displayString' => 'Groom Email', 'key' => 'groom_email', 'value' => $data->groom_email,],
            ['displayString' => 'Groom Passport No', 'key' => 'groom_passport_no', 'value' => $data->groom_passport_no,],
            ['displayString' => 'Groom Residential Address', 'key' => 'groom_residential_address', 'value' => $data->groom_residential_address,],
            ['displayString' => 'Groom Martial Status', 'key' => 'groom_martial_status', 'value' => $data->groom_martial_status,],
            ['displayString' => 'Groom Father Name', 'key' => 'groom_father_name', 'value' => $data->groom_father_name,],
            ['displayString' => 'Groom Father Aadhar No', 'key' => 'groom_father_aadhar_no', 'value' => $data->groom_father_aadhar_no,],
            ['displayString' => 'Groom Mother Name', 'key' => 'groom_mother_name', 'value' => $data->groom_mother_name,],
            ['displayString' => 'Groom Mother Aadhar No', 'key' => 'groom_mother_aadhar_no', 'value' => $data->groom_mother_aadhar_no,],
            ['displayString' => 'Groom Guardian Name', 'key' => 'groom_guardian_name', 'value' => $data->groom_guardian_name,],
            ['displayString' => 'Groom Guardian Aadhar_no', 'key' => 'groom_guardian_aadhar_no', 'value' => $data->groom_guardian_aadhar_no,],
        ]);
    }

    /**
     * | Witness Details
     */
    // public function generateWitnessDetails($data)
    // {
    //     return new Collection([
    //         ['displayString' => 'groom_name', 'key' => 'groom_name', 'value' => $data->groom_name,],
    //         ['displayString' => 'groom_dob', 'key' => 'groom_dob', 'value' => $data->groom_dob,],
    //         ['displayString' => 'groom_age', 'key' => 'groom_age', 'value' => $data->groom_age,],
    //         ['displayString' => 'groom_nationality', 'key' => 'groom_nationality', 'value' => $data->groom_nationality,],
    //         ['displayString' => 'groom_religion', 'key' => 'groom_religion', 'value' => $data->groom_religion],
    //         ['displayString' => 'groom_mobile', 'key' => 'groom_mobile', 'value' => $data->groom_mobile,],
    //         ['displayString' => 'groom_aadhar_no', 'key' => 'groom_aadhar_no', 'value' => $data->groom_aadhar_no,],
    //         ['displayString' => 'groom_email', 'key' => 'groom_email', 'value' => $data->groom_email,],
    //         ['displayString' => 'groom_passport_no', 'key' => 'groom_passport_no', 'value' => $data->groom_passport_no,],
    //         ['displayString' => 'groom_residential_address', 'key' => 'groom_residential_address', 'value' => $data->groom_residential_address,],
    //         ['displayString' => 'groom_martial_status', 'key' => 'groom_martial_status', 'value' => $data->groom_martial_status,],
    //         ['displayString' => 'groom_father_name', 'key' => 'groom_father_name', 'value' => $data->groom_father_name,],
    //         ['displayString' => 'groom_father_aadhar_no', 'key' => 'groom_father_aadhar_no', 'value' => $data->groom_father_aadhar_no,],
    //         ['displayString' => 'groom_mother_name', 'key' => 'groom_mother_name', 'value' => $data->groom_mother_name,],
    //         ['displayString' => 'groom_mother_aadhar_no', 'key' => 'groom_mother_aadhar_no', 'value' => $data->groom_mother_aadhar_no,],
    //         ['displayString' => 'groom_guardian_name', 'key' => 'groom_guardian_name', 'value' => $data->groom_guardian_name,],
    //         ['displayString' => 'groom_guardian_aadhar_no', 'key' => 'groom_guardian_aadhar_no', 'value' => $data->groom_guardian_aadhar_no,],
    //     ]);
    // }

    /**
     * | Witness Details
     */
    public function generateWitnessDetails($witnessDetails)
    {
        return collect($witnessDetails)->map(function ($witnessDetail, $key) {
            return [
                $key + 1,
                $witnessDetail['withnessName'],
                $witnessDetail['withnessMobile'],
                $witnessDetail['withnessAddress'],
            ];
        });
    }

    /**
     * | Generate Card Details
     */
    public function generateCardDtls($data)
    {

        $marriageDetails = new Collection([
            ['displayString' => 'Marriage Date', 'key' => 'marriageDate', 'value' => $data->marriage_date],
            ['displayString' => 'Marriage Place', 'key' => 'marriagePlace', 'value' => $data->marriage_place],
            ['displayString' => 'Groom Name', 'key' => 'groom_name', 'value' => $data->groom_name,],
            ['displayString' => 'Bride Name', 'key' => 'bride_name', 'value' => $data->bride_name,],
            ['displayString' => 'Either of the Bride or Groom belongs to BPL category', 'key' => 'bpl', 'value' => ($data->is_bpl == false) ? 'No' : 'Yes'],
        ]);

        $cardElement = [
            'headerTitle' => "Marriage Details",
            'data' => $marriageDetails
        ];
        return $cardElement;
    }

    /**
     * | Save Request
     */
    public function makeRequest($request)
    {
        $reqs = [
            'bride_name'                   => $request->brideName,
            'bride_dob'                    => $request->brideDob,
            'bride_age'                    => $request->brideAge,
            'bride_nationality'            => $request->brideNationality,
            'bride_religion'               => $request->brideReligion,
            'bride_mobile'                 => $request->brideMobile,
            'bride_aadhar_no'              => $request->brideAadharNo,
            'bride_email'                  => $request->brideEmail,
            'bride_passport_no'            => $request->bridePassportNo,
            'bride_residential_address'    => $request->brideResidentialAddress,
            'bride_martial_status'         => $request->brideMartialStatus,
            'bride_father_name'            => $request->brideFatherName,
            'bride_father_aadhar_no'       => $request->brideFatherAadharNo,
            'bride_mother_name'            => $request->brideMotherName,
            'bride_mother_aadhar_no'       => $request->brideMotherAadharNo,
            'bride_guardian_name'          => $request->brideGuardianName,
            'bride_guardian_aadhar_no'     => $request->brideGuardianAadharNo,
            'groom_name'                   => $request->groomName,
            'groom_dob'                    => $request->groomDob,
            'groom_age'                    => $request->groomAge,
            'groom_aadhar_no'              => $request->groomAadharNo,
            'groom_nationality'            => $request->groomNationality,
            'groom_religion'               => $request->groomReligion,
            'groom_mobile'                 => $request->groomMobile,
            'groom_passport_no'            => $request->groomPassportNo,
            'groom_residential_address'    => $request->groomResidentialAddress,
            'groom_martial_status'         => $request->groomMartialStatus,
            'groom_father_name'            => $request->groomFatherName,
            'groom_father_aadhar_no'       => $request->groomFatherAadharNo,
            'groom_mother_name'            => $request->groomMotherName,
            'groom_mother_aadhar_no'       => $request->groomMotherAadharNo,
            'groom_guardian_name'          => $request->groomGuardianName,
            'groom_guardian_aadhar_no'     => $request->groomGuardianAadharNo,
            'marriage_date'                => $request->marriageDate,
            'marriage_place'               => $request->marriagePlace,
            'witness1_name'                => $request->witness1Name,
            'witness1_mobile_no'           => $request->witness1MobileNo,
            'witness1_residential_address' => $request->witness1ResidentialAddress,
            'witness2_name'                => $request->witness2Name,
            'witness2_residential_address' => $request->witness2ResidentialAddress,
            'witness2_mobile_no'           => $request->witness2MobileNo,
            'witness3_name'                => $request->witness3Name,
            'witness3_mobile_no'           => $request->witnessMobileNo,
            'witness3_residential_address' => $request->witness3ResidentialAddress,
            'appointment_date'             => $request->appointmentDate,
            'marriage_registration_date'   => $request->marriageRegistrationDate,
            'is_bpl'                       => $request->bpl,
            // 'registrar_id'                 => $request->registrarId,
            // 'user_id'                      => $request->userId,
            // 'citizen_id'                   => $request->citizenId,
            // 'application_no'               => $request->applicationNo,
            // 'initiator_role_id'            => $request->initiatorRoleId,
            // 'finisher_role_id'             => $request->finisherRoleId,
            // 'workflow_id'                  => $request->workflowId
        ];

        return $reqs;
    }
}

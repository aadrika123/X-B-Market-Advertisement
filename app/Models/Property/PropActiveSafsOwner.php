<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveSafsOwner extends Model
{
    use HasFactory;


    /**
     * | Get Owner Dtls by Saf Id
     */
    public function getOwnerDtlsBySafId($safId)
    {
        return PropActiveSafsOwner::where('saf_id', $safId)
            ->select(
                'owner_name as ownerName',
                'mobile_no as mobileNo',
                'guardian_name as guardianName',
                'email',
                'gender',
                'is_armed_force',
                'is_specially_abled'
            )
            ->orderBy('id')
            ->get();
    }
}

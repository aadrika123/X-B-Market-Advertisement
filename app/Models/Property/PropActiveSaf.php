<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropActiveSaf extends Model
{
    use HasFactory;

    /**
     * | Get Saf Details by Saf No
     * | @param SafNo
     */
    public function getSafDtlBySaf()
    {
        return DB::table('prop_active_safs as s')
            ->select(
                's.*',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no',
            )
            ->join('ulb_ward_masters as u', 's.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 's.new_ward_mstr_id', '=', 'u1.id')
            ->where('s.status', 1);
    }


    /**
     * | Get Saf Details by Saf No
     * | @param SafNo
     */
    public function getSafDtlBySafUlbNo($safNo, $ulbId)
    {
        return DB::table('prop_active_safs as s')
            ->where('s.saf_no', $safNo)
            ->where('s.ulb_id', $ulbId)
            ->select(
                's.id',
                's.saf_no',
                's.ward_mstr_id',
                's.new_ward_mstr_id',
                's.elect_consumer_no',
                's.elect_acc_no',
                's.elect_bind_book_no',
                's.elect_cons_category',
                's.prop_address',
                's.corr_address',
                's.prop_pin_code',
                's.corr_pin_code',
                's.area_of_plot as total_area_in_desimal',
                's.apartment_details_id',
                's.prop_type_mstr_id',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no',
            )
            ->join('ulb_ward_masters as u', 's.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 's.new_ward_mstr_id', '=', 'u1.id')
            ->where('s.status', 1)
            ->first();
    }

    /**
     * | Get citizen safs
     */
    public function getCitizenSafs($citizenId, $ulbId)
    {
        return PropActiveSaf::select('id', 'saf_no', 'citizen_id')
            ->where('citizen_id', $citizenId)
            ->where('ulb_id', $ulbId)
            ->orderByDesc('id')
            ->get();
    }
}

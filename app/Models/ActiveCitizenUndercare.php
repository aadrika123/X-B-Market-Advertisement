<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveCitizenUndercare extends Model
{
    use HasFactory;

    /**
     * | Get Tagged Property by Citizen Id
     */
    public function getTaggedPropsByCitizenId($citizenId)
    {
        return ActiveCitizenUndercare::where('citizen_id', $citizenId)
            ->where('deactive_status', false)
            ->get();
    }
}

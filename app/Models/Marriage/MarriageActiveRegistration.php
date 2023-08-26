<?php

namespace App\Models\Marriage;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarriageActiveRegistration extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * |
     */
    public function saveRegistration($request)
    {
        $mMarriageActiveRegistration = new MarriageActiveRegistration();
        return $registrationDtl = $mMarriageActiveRegistration->create($request);
        // return [
        //     "id" => $registrationDtl->id,
        //     "applicationNo" => $registrationDtl->application_no,
        // ];
    }
}

<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function feedback($req)
    {
        $mfeedback = new Feedback();
        $mfeedback->application_id   = $req['applicationId'];
        $mfeedback->remarks   = $req['remarks'];
        $mfeedback->save();
    }

}

<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyHoardingRejectedApplication extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Get Rig rejected application details by id
     */
    public function getRejectedApplicationById($id)
    {
        return AgencyHoardingRejectedApplication::join('ulb_masters', 'ulb_masters.id', '=', 'agency_hoarding_rejected_applications.ulb_id')
            ->where('agency_hoarding_rejected_applications.application_id', $id)
            ->where('agency_hoarding_rejected_applications.status', '<>', 0);
    }
}

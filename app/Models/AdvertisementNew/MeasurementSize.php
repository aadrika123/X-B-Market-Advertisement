<?php

namespace App\Models\AdvertisementNew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeasurementSize extends Model
{
    use HasFactory;
    #get measurement data 
    public function getMeasureSQfT($squareFeetId)
    {
        return self::select(
            'id',
            'measurement',
            'sq_ft'
        )
            ->where('id', $squareFeetId)
            ->where('status', 1)
            ->first();
    }
    # get all measurement
    public function getAllMeasurement()
    {
        return self::select('id', 'measurement')
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiMaster extends Model
{
    use HasFactory;

    /**
     * | Get Api End Point using the id 
     */
    public function getApiEndPoint($apiId)
    {
        return ApiMaster::where('id', $apiId)
            ->where('discontinued', '!=', true);
    }
}

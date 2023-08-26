<?php

namespace App\Models\Pet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Predis\Response\Status;

class MPetFee extends Model
{
    use HasFactory;

    /**
     * | Get fee details according to id
     */
    public function getFeeById($id)
    {
        return MPetFee::where('id', $id)
            ->where('status', 1)
            ->first();
    }
}

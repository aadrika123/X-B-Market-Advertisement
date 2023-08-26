<?php

namespace App\Models\Pet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPetOccurrenceType extends Model
{
    use HasFactory;

    /**
     * | 
     */
    public function listOccurenceType()
    {
        return MPetOccurrenceType::where('status', 1)
            ->orderByDesc('id');
    }
}

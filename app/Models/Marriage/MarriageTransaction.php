<?php

namespace App\Models\Marriage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarriageTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function store($req)
    {
        $tranDtl = MarriageTransaction::create($req);
        return $tranDtl;
    }
}

<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarShopLog extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $fillable = [
        'shop_id',
        'user_id',
        'date',
        'change_data'
    ];
}

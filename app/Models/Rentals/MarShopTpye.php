<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarShopTpye extends Model
{
    use HasFactory;

    protected $guarded=[];

    public function listShopType(){
        return self::where('status','1')->get();
    }
}

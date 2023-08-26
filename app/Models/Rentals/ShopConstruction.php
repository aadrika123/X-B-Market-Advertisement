<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopConstruction extends Model
{
    use HasFactory;


    public function listConstruction(){
        return self::select('id','construction_type')->where('status','1')->get();
    }
}

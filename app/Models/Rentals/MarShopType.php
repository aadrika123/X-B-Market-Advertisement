<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarShopType extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function listShopType(){
        return self::select('id','shop_type')->where('status','1')->orderBy('id')->get();
    }
}

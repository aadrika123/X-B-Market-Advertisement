<?php

namespace App\Models\Bandobastee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarTollPriceList extends Model
{
    use HasFactory;

    /**
     * | Get Market Toll price List From Model
     */
    public function getTollPriceList(){
        return self::select('id','toll_type','rate')->where('status','1')->get();
    }
}

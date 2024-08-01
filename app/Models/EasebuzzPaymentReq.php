<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EasebuzzPaymentReq extends Model
{
    use HasFactory;    
    protected $connection = 'pgsql_masters';
    protected $guarded = [];
}

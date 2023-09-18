<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MCircle extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'm_circle';

    /**
     * | Get Circle Name By ULB Id 
     */
    public function getCircleNameByUlbId($circleName, $ulbId)
    {
        return MCircle::select('*')
            ->where('ulb_id', $ulbId)
            ->where('circle_name', $circleName)
            ->where('is_active', '1')
            ->get();
    }
    /**
     * | Get Circle List By Ulb Id
     */
    public function getCircleByUlbId($ulbId)
    {
        return MCircle::select('*')
            ->where('ulb_id', $ulbId)
            ->where('is_active', '1')
            ->get();
    }

    /**
     * | Get All Active Circle List
     */
    public function getAllActive()
    {
        return MCircle::select('*')
            ->where('is_active', 1)
            ->get();
    }
}

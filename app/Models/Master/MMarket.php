<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MMarket extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'm_market';


    public function getMarketNameByCircleId($marketName, $circleId)
    {
        return MMarket::select('*')
            ->where('circle_id', $circleId)
            ->where('market_name', $marketName)
            ->where('is_active', '1')
            ->get();
    }

    public function getMarketByCircleId($circleId)
    {
        return MMarket::select('*')
            ->where('circle_id', $circleId)
            ->where('is_active', '1')
            ->get();
    }

    public function getAllActive()
    {
        return MMarket::select('*')
            ->where('is_active', 1)
            ->get();
    }
}

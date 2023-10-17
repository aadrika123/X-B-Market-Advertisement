<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MMarket extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'm_market';

    /**
     * | Get Market Name By Circle ID
     */
    public function getMarketNameByCircleId($marketName, $circleId)
    {
        return MMarket::select('*')
            ->where('circle_id', $circleId)
            // ->where('market_name', $marketName)
            ->whereRaw('LOWER(market_name) = (?)', [strtolower($marketName)])
            ->where('is_active', '1')
            ->get();
    }

    /**
     * | Get List of Market By Circle Id
     */
    public function getMarketByCircleId($circleId)
    {
        return MMarket::select('*')
            ->where('circle_id', $circleId)
            ->where('is_active', '1')
            ->orderBy('id', 'ASC')
            ->get();
    }

    /**
     * | Get All Active Market
     */
    public function getAllActive()
    {
        return MMarket::select('*')
            ->where('is_active', 1)
            ->get();
    }
}

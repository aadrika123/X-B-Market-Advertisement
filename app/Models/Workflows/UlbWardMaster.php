<?php

namespace App\Models\Workflows;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UlbWardMaster extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $_applicationDate;

    // Initializing construction
    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
    }
    /**
     * | get the ward by Id
     * | @param id
     */
    public function getWard($id)
    {
        return UlbWardMaster::where('id', $id)
            ->firstOrFail();
    }
}

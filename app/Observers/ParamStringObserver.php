<?php

namespace App\Observers;

use App\Models\Param\RefAdvParamstring;
use Illuminate\Support\Facades\Cache;

class ParamStringObserver
{
    public function creating(RefAdvParamstring $refAdvParamstring)
    {
        Cache::forget("adv_param_strings.{$refAdvParamstring->ulb_id}");
    }
    /**
     * Handle the RefAdvParamstring "created" event.
     *
     * @param  \App\Models\RefAdvParamstring  $refAdvParamstring
     * @return void
     */
    public function created(RefAdvParamstring $refAdvParamstring)
    {
        Cache::forget("adv_param_strings.{$refAdvParamstring->ulb_id}");
    }

    /**
     * Handle the RefAdvParamstring "updated" event.
     *
     * @param  \App\Models\RefAdvParamstring  $refAdvParamstring
     * @return void
     */
    public function updated(RefAdvParamstring $refAdvParamstring)
    {
        Cache::forget("adv_param_strings.{$refAdvParamstring->ulb_id}");
    }

    /**
     * Handle the RefAdvParamstring "deleted" event.
     *
     * @param  \App\Models\RefAdvParamstring  $refAdvParamstring
     * @return void
     */
    public function deleted(RefAdvParamstring $refAdvParamstring)
    {
        Cache::forget("adv_param_strings.{$refAdvParamstring->ulb_id}");
    }

    /**
     * Handle the RefAdvParamstring "restored" event.
     *
     * @param  \App\Models\RefAdvParamstring  $refAdvParamstring
     * @return void
     */
    public function restored(RefAdvParamstring $refAdvParamstring)
    {
        Cache::forget("adv_param_strings.{$refAdvParamstring->ulb_id}");
    }

    /**
     * Handle the RefAdvParamstring "force deleted" event.
     *
     * @param  \App\Models\RefAdvParamstring  $refAdvParamstring
     * @return void
     */
    public function forceDeleted(RefAdvParamstring $refAdvParamstring)
    {
        Cache::forget("adv_param_strings.{$refAdvParamstring->ulb_id}");
    }

    public function retrieved(RefAdvParamstring $refAdvParamstring)
    {
        return 'Hii';
    }
}

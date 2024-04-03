<?php

namespace App\Pipelines\Advertisement;

use Closure;

class SearchByHoardingNo
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('hoardingNo')) {
            return $next($request);
        }
        return $next($request)
            ->where('hoarding_no', 'ilike', '%' . request()->input('hoardingNo') . '%');
    }
}

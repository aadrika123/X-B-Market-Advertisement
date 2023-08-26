<?php

namespace App\Pipelines\Marriage;

use Closure;

class SearchByName
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('name')) {
            return $next($request);
        }
        return $next($request)
            ->where('bride_name', 'ilike', '%' . request()->input('name') . '%')
            ->orwhere('groom_name', 'ilike', '%' . request()->input('name') . '%');
    }
}

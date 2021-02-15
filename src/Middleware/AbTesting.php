<?php

namespace PeterVincze\AbTesting\Middleware;

use Closure;
use Illuminate\Http\Request;

class AbTesting
{
    public function handle(Request $request, Closure $next)
    {
        if (defined('LARAVEL_START')) {
   	        app('ab-testing')->autoCompleteGoal($request);
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PosAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!session()->has('pos_user')) {
            return redirect()->route('pos.login');
        }

        return $next($request);
    }
}

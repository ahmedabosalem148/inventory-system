<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('admin')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}

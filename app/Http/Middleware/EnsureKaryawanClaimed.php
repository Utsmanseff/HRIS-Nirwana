<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureKaryawanClaimed
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->sudahKlaim() && ! $request->routeIs('klaim', 'logout')) {
            return redirect()->route('klaim');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PastikanAkunAktif
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->akunAktif()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['nip' => 'Akun dinonaktifkan. Hubungi admin sistem.']);
        }

        return $next($request);
    }
}

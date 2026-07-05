<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $g = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect('/login')->withErrors(['nip' => 'Gagal masuk dengan Google.']);
        }

        $user = User::where('google_id', $g->getId())->orWhere('email', $g->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name' => $g->getName() ?: $g->getEmail(),
                'email' => $g->getEmail(),
                'google_id' => $g->getId(),
                'avatar_url' => $g->getAvatar(),
            ]);
        } elseif (! $user->google_id) {
            $user->update(['google_id' => $g->getId(), 'avatar_url' => $g->getAvatar()]);
        }

        if (! $user->akunAktif()) {
            return redirect('/login')->withErrors(['nip' => 'Akun dinonaktifkan. Hubungi admin sistem.']);
        }

        Auth::login($user, true);
        session()->regenerate();

        // Middleware ensure.claimed akan mengarahkan ke /klaim bila belum klaim.
        return $user->sudahKlaim() ? redirect()->intended('/beranda') : redirect('/klaim');
    }
}

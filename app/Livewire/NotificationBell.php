<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationBell extends Component
{
    public bool $terbuka = false;

    public function tandaiDibaca(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    public function tandaiSemuaDibaca(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.notification-bell', [
            'jumlahBelumDibaca' => $user->unreadNotifications()->count(),
            'daftar' => $user->notifications()->latest()->limit(10)->get(),
        ]);
    }
}

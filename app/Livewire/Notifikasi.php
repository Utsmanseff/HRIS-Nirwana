<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Notifikasi')]
class Notifikasi extends Component
{
    use WithPagination;

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

        return view('livewire.notifikasi', [
            'jumlahBelumDibaca' => $user->unreadNotifications()->count(),
            'daftar' => $user->notifications()->latest()->paginate(20),
        ]);
    }
}

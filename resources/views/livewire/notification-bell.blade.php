<div class="relative" x-data="{ open: @entangle('terbuka') }">
    <button type="button" class="btn btn-ghost btn-icon relative" aria-label="notifikasi" @click="open = !open">
        <x-icon name="bell" :size="20" />
        @if ($jumlahBelumDibaca > 0)
            <span class="absolute top-1.5 right-1.5 min-w-4 h-4 px-1 rounded-full bg-danger-500 text-white text-[9px] font-bold grid place-items-center">{{ $jumlahBelumDibaca }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false"
         class="absolute right-0 mt-2 w-80 max-w-[90vw] card shadow-lg z-50 overflow-hidden">
        <div class="flex items-center justify-between px-3 py-2 border-b border-neutral-200">
            <span class="font-semibold text-sm">Notifikasi</span>
            @if ($jumlahBelumDibaca > 0)
                <button class="text-xs text-brand-600 hover:underline" wire:click="tandaiSemuaDibaca">Tandai semua dibaca</button>
            @endif
        </div>

        <div class="px-3 py-2 border-b border-neutral-200">
            <x-push-subscribe />
        </div>

        <div class="max-h-96 overflow-y-auto divide-y divide-neutral-100">
            @forelse ($daftar as $n)
                <a href="{{ $n->data['url'] ?? '#' }}" wire:click="tandaiDibaca('{{ $n->id }}')"
                   class="flex gap-2 px-3 py-2.5 hover:bg-neutral-50 {{ $n->read_at ? '' : 'bg-brand-50/40' }}">
                    <span class="mt-1 w-2 h-2 rounded-full shrink-0 {{ $n->read_at ? 'bg-transparent' : 'bg-brand-500' }}"></span>
                    <div class="min-w-0">
                        <div class="text-sm text-neutral-700">{{ $n->data['pesan'] ?? '' }}</div>
                        <div class="text-[11px] text-neutral-400 mt-0.5">{{ $n->created_at->diffForHumans() }}</div>
                    </div>
                </a>
            @empty
                <div class="px-3 py-8 text-center text-sm text-neutral-400">Belum ada notifikasi.</div>
            @endforelse
        </div>
    </div>
</div>

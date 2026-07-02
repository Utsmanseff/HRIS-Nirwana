{{-- Pager custom — view pagination bawaan Livewire pakai util responsif Tailwind yang rusak app-wide. --}}
@if ($paginator->hasPages())
    <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-t border-neutral-100">
        <span class="text-sm text-neutral-500 tnum">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
        </span>
        <div class="flex gap-2">
            <button wire:click="previousPage" @disabled($paginator->onFirstPage()) class="btn btn-secondary btn-sm">Sebelumnya</button>
            <button wire:click="nextPage" @disabled(! $paginator->hasMorePages()) class="btn btn-secondary btn-sm">Berikutnya</button>
        </div>
    </div>
@endif

<div class="mx-auto max-w-4xl">
    <h1 class="text-xl font-bold mb-4">Kelola Cuti</h1>
    <div class="card">
        <div class="px-4 flex gap-1 border-b border-neutral-100 overflow-x-auto whitespace-nowrap">
            <button wire:click="$set('tab','hari-libur')" class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab==='hari-libur' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">Hari Libur</button>
            <button wire:click="$set('tab','jenis')" class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab==='jenis' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">Jenis Cuti</button>
            <button wire:click="$set('tab','penyesuaian')" class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab==='penyesuaian' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">Penyesuaian Jatah</button>
        </div>
        <div class="card-pad text-sm text-neutral-500">Segera diisi.</div>
    </div>
</div>

{{-- Node pohon org — rekursif. Terima $unit (OrgUnit dgn children + karyawan_count). --}}
@php $kepala = $unit->kepala(); @endphp
<div class="node group flex items-center gap-2 px-2.5 py-2 rounded-md hover:bg-neutral-100">
    <span class="w-6 h-6 rounded-md grid place-items-center text-[10px] font-bold
        {{ in_array($unit->tipe->value, ['direktur', 'bidang', 'bagian']) ? 'bg-brand-100 text-brand-700' : 'bg-neutral-100 text-neutral-500' }}">
        {{ mb_strtoupper(mb_substr($unit->tipe->value, 0, 1)) }}
    </span>
    <span class="text-sm flex-1 {{ $unit->tipe->value !== 'unit' ? 'font-semibold' : '' }}">
        {{ $unit->nama }} <span class="text-xs font-normal text-neutral-400">· {{ $unit->tipe->label() }}</span>
        <span class="block text-xs text-neutral-500">
            @if ($kepala)
                Kepala: {{ $kepala->nama_lengkap }}
            @else
                <span class="text-neutral-400">Belum ada kepala</span>
            @endif
        </span>
    </span>
    <span class="badge badge-neutral">{{ $unit->karyawan_count }}</span>
    <span class="opacity-0 group-hover:opacity-100 flex gap-1 transition">
        <button wire:click="bukaSetKepala({{ $unit->id }})" class="btn btn-ghost btn-sm" title="Set kepala">Kepala</button>
        <button wire:click="bukaJabatan({{ $unit->id }})" class="btn btn-ghost btn-sm" title="Kelola jabatan">Jabatan</button>
        <button wire:click="baru({{ $unit->id }})" class="btn btn-ghost btn-sm" title="Tambah anak">+</button>
        <button wire:click="edit({{ $unit->id }})" class="btn btn-ghost btn-sm" title="Ubah unit">Ubah</button>
    </span>
</div>
@if ($unit->children->isNotEmpty())
    <div class="branch ml-4 pl-2 border-l border-neutral-200 space-y-0.5">
        @foreach ($unit->children as $child)
            @include('livewire.sdm.partials.org-node', ['unit' => $child])
        @endforeach
    </div>
@endif

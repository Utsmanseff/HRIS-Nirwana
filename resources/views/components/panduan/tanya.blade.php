{{-- Tombol "?" kontekstual. Dirender HANYA bila rute saat ini punya bab panduan;
     tombol yang membuang orang ke daftar isi umum lebih mengecewakan daripada
     tak ada tombol sama sekali. Sheet-nya sendiri global (lihat panduan.sheet). --}}
@props(['class' => ''])
@php
    $target = \App\Support\Panduan::untukRute(request()->route()?->getName());
@endphp
@if ($target)
    <button type="button" class="{{ $class }}"
            aria-label="Bantuan halaman ini" title="Bantuan halaman ini"
            @click="$store.tanyaPanduan.tampil('{{ $target['slug'] }}', '{{ $target['bagian'] }}')">
        <x-icon name="help" :size="20" />
    </button>
@endif

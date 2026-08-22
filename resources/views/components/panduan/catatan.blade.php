@props(['tipe' => 'info'])
@php
    // Sengaja token -600: theme.css membalik nilai -600 di mode gelap, sedangkan
    // -500 tidak dibalik sehingga jadi terlalu gelap di atas latar gelap.
    $warna = [
        'info' => ['--info-600', 'Catatan'],
        'awas' => ['--warning-600', 'Perhatian'],
        'bahaya' => ['--danger-600', 'Jangan Dilakukan'],
    ][$tipe] ?? ['--info-600', 'Catatan'];
@endphp
<div class="card card-pad my-4 border-s-4" style="border-inline-start-color:var({{ $warna[0] }})">
    <div class="text-[12px] font-bold uppercase tracking-wide mb-1" style="color:var({{ $warna[0] }})">{{ $warna[1] }}</div>
    <div class="text-[13.5px] leading-relaxed">{{ $slot }}</div>
</div>

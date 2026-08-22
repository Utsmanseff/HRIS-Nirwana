@props(['judul' => null])
@if ($judul)
    <h3 class="text-[15px] font-bold mt-5 mb-2">{{ $judul }}</h3>
@endif
<ol class="list-decimal ms-5 space-y-2 text-[14px] leading-relaxed">
    {{ $slot }}
</ol>

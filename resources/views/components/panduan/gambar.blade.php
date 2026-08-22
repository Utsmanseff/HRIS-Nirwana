@props(['src', 'caption' => null, 'lebar' => 'sempit'])
<figure class="my-4">
    <img src="{{ asset('img/panduan/'.$src) }}" alt="{{ $caption ?? 'Tangkapan layar panduan' }}"
         loading="lazy"
         @class(['rounded-xl border mx-auto block', 'max-w-[300px] w-full' => $lebar === 'sempit', 'w-full' => $lebar !== 'sempit'])
         style="border-color:var(--border)">
    @if ($caption)
        <figcaption class="text-[12px] text-center mt-1.5" style="color:var(--text-muted)">{{ $caption }}</figcaption>
    @endif
</figure>

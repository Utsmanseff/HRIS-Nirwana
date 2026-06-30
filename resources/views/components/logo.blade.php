@props(['size' => 24])
{{-- RSU Nirwana mark (approx): green arc (C) + lime arc + red cross.
     SWAP: replace with the official logo file when ready. --}}
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 32 32" fill="none" {{ $attributes }}>
    <path d="M23 5.5A12 12 0 1 0 23 26.5" stroke="#0f8a3d" stroke-width="3.4" stroke-linecap="round"/>
    <path d="M21.5 9.2A8 8 0 1 0 21.5 22.8" stroke="#8cc63f" stroke-width="3" stroke-linecap="round"/>
    <path d="M19.5 13h3.2V9.8h3.8V13H29v3.8h-2.5V20h-3.8v-3.2h-3.2z" fill="#ed1c24"/>
</svg>

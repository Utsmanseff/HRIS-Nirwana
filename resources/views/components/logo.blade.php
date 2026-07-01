@props(['size' => 24])
{{-- Mark/lambang RSU Nirwana (persegi: lengkung hijau + salib merah). Dipakai di
     sidebar, login, klaim, appbar, dan app-icon — semuanya dalam wadah persegi.
     CATATAN: wordmark horizontal (RSU22Nirwana.png) BUKAN di sini — itu khusus kop
     dokumen ekspor (dipasang di fitur laporan/ekspor). --}}
<img src="{{ asset('img/android-chrome-192x192.png') }}" alt="RSU Nirwana"
     width="{{ $size }}" height="{{ $size }}"
     style="width:{{ $size }}px;height:{{ $size }}px;object-fit:contain;display:block"
     {{ $attributes }}>

@props(['size' => 24])
{{-- Wordmark resmi RSU Nirwana (horizontal). Tinggi mengikuti $size; lebar auto. --}}
<img src="{{ asset('img/RSU22Nirwana.png') }}" alt="RSU Nirwana"
     height="{{ $size }}" style="height:{{ $size }}px;width:auto;display:block"
     {{ $attributes }}>

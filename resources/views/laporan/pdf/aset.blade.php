@extends('laporan.pdf.layout')

@section('judul', 'Daftar Aset')

@section('keterangan-filter')
    {{ $keteranganFilter }}
@endsection

@section('isi')
<table class="data">
    <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Tim</th><th>Lokasi</th><th>Status</th><th>Penanggung Jawab</th></tr></thead>
    <tbody>
        @foreach ($aset as $a)
            <tr>
                <td>{{ $a->kode }}</td>
                <td>{{ $a->nama }}</td>
                <td>{{ $a->kategori?->nama }}</td>
                <td>{{ $a->kategori?->tim?->label() }}</td>
                <td>{{ $a->orgUnit?->nama }}</td>
                <td>{{ $a->status->label() }}</td>
                <td>{{ $a->penanggungJawab?->nama_lengkap }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="meta" style="margin-top:8px">Total: {{ $aset->count() }} aset</div>
@endsection

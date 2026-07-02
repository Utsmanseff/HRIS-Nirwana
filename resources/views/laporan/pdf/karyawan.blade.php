@extends('laporan.pdf.layout')

@section('judul', 'Daftar Karyawan')

@section('keterangan-filter')
    {{ $keteranganFilter }}
@endsection

@section('isi')
<table class="data">
    <thead><tr><th>NIP</th><th>Nama</th><th>Unit</th><th>Jabatan</th><th>Kontrak Terakhir</th><th>Tgl Akhir</th><th>Status</th></tr></thead>
    <tbody>
        @foreach ($karyawan as $k)
            <tr>
                <td>{{ $k->nip }}</td>
                <td>{{ $k->nama_lengkap }}</td>
                <td>{{ $k->orgUnit?->nama }}</td>
                <td>{{ $k->jabatan?->nama }}</td>
                <td>{{ $k->kontrakTerbaru?->jenis->label() }}</td>
                <td>{{ $k->kontrakTerbaru?->tanggal_akhir?->format('d-m-Y') }}</td>
                <td>{{ ucfirst($k->status->value) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="meta" style="margin-top:8px">Total: {{ $karyawan->count() }} karyawan</div>
@endsection

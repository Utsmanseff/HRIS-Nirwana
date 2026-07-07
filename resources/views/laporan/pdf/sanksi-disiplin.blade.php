@extends('laporan.pdf.layout')

@section('judul', 'Rekap Sanksi Disiplin')

@section('keterangan-filter')
    {{ $keteranganFilter }}
@endsection

@section('isi')
<table class="data">
    <thead><tr><th>NIP</th><th>Karyawan</th><th>Unit</th><th>Tingkat</th><th>Tgl Kejadian</th><th>Pengusul</th><th>Nomor</th><th>Status</th></tr></thead>
    <tbody>
        @foreach ($sanksi as $s)
            <tr>
                <td>{{ $s->karyawan->nip }}</td>
                <td>{{ $s->karyawan->nama_lengkap }}</td>
                <td>{{ $s->karyawan->orgUnit?->nama }}</td>
                <td>{{ $s->tingkat->label() }}</td>
                <td>{{ $s->tanggal_kejadian->format('d-m-Y') }}</td>
                <td>{{ $s->pengusul->nama_lengkap }}</td>
                <td>{{ $s->nomor_surat }}</td>
                <td>{{ $s->status->label() }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="meta" style="margin-top:8px">Total: {{ $sanksi->count() }} sanksi</div>
@endsection

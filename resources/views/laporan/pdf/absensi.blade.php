@extends('laporan.pdf.layout')

@section('judul', 'Laporan Absensi')

@section('keterangan-filter')
    {{ $keteranganFilter }}
@endsection

@section('isi')
<table class="data">
    <thead>
        <tr>
            <th>Tanggal</th><th>Karyawan</th><th>NIP</th><th>Shift</th>
            <th>Masuk</th><th>Pulang</th><th>Jam Kerja</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($baris as $a)
            <tr>
                <td>{{ $a->tanggal_kerja->format('d/m/Y') }}</td>
                <td>{{ $a->karyawan->nama_lengkap }}</td>
                <td>{{ $a->karyawan->nip }}</td>
                <td>{{ $a->shift_nama ?? '-' }}</td>
                <td>{{ $a->jam_masuk?->format('H:i') ?? '-' }}</td>
                <td>{{ $a->jam_pulang?->format('H:i') ?? '-' }}</td>
                <td>{{ $a->totalMenit() ? intdiv($a->totalMenit(), 60).'j '.($a->totalMenit() % 60).'m' : '-' }}</td>
                <td>{{ $a->labelStatus()[0] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="meta" style="margin-top:8px">Total: {{ $baris->count() }} baris</div>
@endsection

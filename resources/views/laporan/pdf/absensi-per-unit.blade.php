@extends('laporan.pdf.layout')

@section('judul', 'Laporan Absensi per Unit')

@section('keterangan-filter')
    {{ $keteranganFilter }}
@endsection

@section('isi')
@forelse ($grup as $i => $g)
    <h2 style="font-size:12px; color:#14532d; margin:{{ $i === 0 ? '0' : '0' }} 0 4px; {{ $i > 0 ? 'page-break-before: always;' : '' }}">
        {{ $g['unit']->nama }} <span style="font-size:9px; color:#777">({{ $g['baris']->count() }} baris)</span>
    </h2>
    <table class="data">
        <thead>
            <tr>
                <th>Tanggal</th><th>Karyawan</th><th>NIP</th><th>Shift</th>
                <th>Masuk</th><th>Pulang</th><th>Jam Kerja</th><th>Status</th><th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($g['baris'] as $a)
                <tr>
                    <td>{{ $a->tanggal_kerja->format('d/m/Y') }}</td>
                    <td>{{ $a->karyawan->nama_lengkap }}</td>
                    <td>{{ $a->karyawan->nip }}</td>
                    <td>{{ $a->shift_nama ?? '-' }}</td>
                    <td>{{ $a->jam_masuk?->format('H:i') ?? '-' }}</td>
                    <td>{{ $a->jam_pulang?->format('H:i') ?? '-' }}</td>
                    <td>{{ $a->totalMenit() ? intdiv($a->totalMenit(), 60).'j '.($a->totalMenit() % 60).'m' : '-' }}</td>
                    <td>{{ $a->labelStatus()[0] }}</td>
                    <td>{{ $keterangan[$a->id] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@empty
    <div class="meta">Tak ada data pada filter ini.</div>
@endforelse
@endsection

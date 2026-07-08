@extends('laporan.pdf.layout')

@section('judul', 'Daftar Tiket')

@section('keterangan-filter')
    {{ $keteranganFilter }}
@endsection

@section('isi')
@if (count($metrik))
<table class="data" style="margin-bottom:10px">
    <thead><tr><th>Tim</th><th>Jumlah</th><th>Rata Respon (mnt)</th><th>Rata Penyelesaian (mnt)</th></tr></thead>
    <tbody>
        @foreach ($metrik as $m)
            <tr>
                <td>{{ \App\Enums\TimTeknis::from($m['tim'])->label() }}</td>
                <td>{{ $m['jumlah'] }}</td>
                <td>{{ $m['rata_respon'] !== null ? round($m['rata_respon']) : '—' }}</td>
                <td>{{ $m['rata_penyelesaian'] !== null ? round($m['rata_penyelesaian']) : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

<table class="data">
    <thead><tr><th>Nomor</th><th>Judul</th><th>Jenis</th><th>Tim</th><th>Pelapor</th><th>Prioritas</th><th>Status</th><th>Lapor</th></tr></thead>
    <tbody>
        @foreach ($tiket as $t)
            <tr>
                <td>{{ $t->nomor }}</td>
                <td>{{ $t->judul }}</td>
                <td>{{ $t->jenis->label() }}</td>
                <td>{{ $t->tim->label() }}</td>
                <td>{{ $t->pelapor?->nama_lengkap ?? 'Internal' }}</td>
                <td>{{ $t->prioritas->label() }}</td>
                <td>{{ $t->status->label() }}</td>
                <td>{{ $t->waktu_lapor->format('Y-m-d H:i') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="meta" style="margin-top:8px">Total: {{ $tiket->count() }} tiket</div>
@endsection

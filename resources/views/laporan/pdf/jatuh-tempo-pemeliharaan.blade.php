@extends('laporan.pdf.layout')

@section('judul', 'Aset Jatuh Tempo Pemeliharaan')

@section('keterangan-filter')
    {{ $keteranganFilter }}
@endsection

@section('isi')
<table class="data">
    <thead><tr><th>Kode</th><th>Aset</th><th>Kategori</th><th>Jadwal</th><th>Terakhir</th><th>Berikutnya</th><th>Sisa Hari</th></tr></thead>
    <tbody>
        @foreach ($pengingat as $p)
            <tr>
                <td>{{ $p->jadwal->aset->kode }}</td>
                <td>{{ $p->jadwal->aset->nama }}</td>
                <td>{{ $p->jadwal->aset->kategori?->nama }}</td>
                <td>{{ $p->jadwal->nama }}</td>
                <td>{{ $p->jadwal->terakhir_dilakukan?->format('d-m-Y') ?? 'belum pernah' }}</td>
                <td>{{ $p->jadwal->berikutnya()?->format('d-m-Y') ?? '—' }}</td>
                <td>{{ $p->sisaHari < 0 ? 'lewat '.abs($p->sisaHari).' hari' : $p->sisaHari.' hari' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="meta" style="margin-top:8px">Total: {{ $pengingat->count() }} jadwal</div>
@endsection

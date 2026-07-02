@extends('laporan.pdf.layout')

@section('judul', 'Pengingat Kontrak (Akan Berakhir / Terlewat)')

@section('keterangan-filter')
    Seluruh karyawan aktif · derived dari kontrak terakhir per karyawan
@endsection

@section('isi')
<table class="data">
    <thead><tr><th>NIP</th><th>Nama</th><th>Unit</th><th>Tahap</th><th>Tgl Akhir</th><th>Sisa Hari</th><th>Status</th></tr></thead>
    <tbody>
        @foreach ($pengingat as $p)
            <tr>
                <td>{{ $p->karyawan->nip }}</td>
                <td>{{ $p->karyawan->nama_lengkap }}</td>
                <td>{{ $p->karyawan->orgUnit?->nama }}</td>
                <td>{{ $p->kontrak->jenis->label() }}</td>
                <td>{{ $p->kontrak->tanggal_akhir->format('d-m-Y') }}</td>
                <td>{{ $p->sisaHari }}</td>
                <td>{{ $p->sisaHari < 0 ? 'Terlewat' : 'Akan berakhir' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="meta" style="margin-top:8px">Total: {{ $pengingat->count() }} pengingat</div>
@endsection

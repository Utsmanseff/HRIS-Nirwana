@extends('laporan.pdf.layout')

@section('judul', 'Rekap Pengajuan Cuti')

@section('keterangan-filter')
    {{ $keteranganFilter }}
@endsection

@section('isi')
<table class="data">
    <thead><tr><th>NIP</th><th>Pemohon</th><th>Unit</th><th>Jenis</th><th>Mulai</th><th>Selesai</th><th>Hari</th><th>Status</th></tr></thead>
    <tbody>
        @foreach ($pengajuan as $p)
            <tr>
                <td>{{ $p->karyawan->nip }}</td>
                <td>{{ $p->karyawan->nama_lengkap }}</td>
                <td>{{ $p->karyawan->orgUnit?->nama }}</td>
                <td>{{ $p->jenisCuti->nama }}</td>
                <td>{{ $p->tanggal_mulai->format('d-m-Y') }}</td>
                <td>{{ $p->tanggal_selesai->format('d-m-Y') }}</td>
                <td>{{ $p->jumlah_hari }}</td>
                <td>{{ ucfirst($p->status->value) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="meta" style="margin-top:8px">Total: {{ $pengajuan->count() }} pengajuan</div>
@endsection

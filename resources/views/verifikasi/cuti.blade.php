@extends('verifikasi.layout')

@section('subjudul', 'Verifikasi Surat Keterangan Cuti')

@section('rincian')
    @unless ($invalid ?? false)
        <div><dt>Tanggal</dt><dd>{{ $tanggalMulai }} – {{ $tanggalSelesai }}</dd></div>
        <div><dt>Jumlah Hari</dt><dd>{{ $jumlahHari }} hari</dd></div>
        <div><dt>Atas Nama</dt><dd>{{ $karyawan }}</dd></div>
    @endunless
@endsection

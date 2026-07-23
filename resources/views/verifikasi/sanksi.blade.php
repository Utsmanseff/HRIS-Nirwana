@extends('verifikasi.layout')

@section('subjudul', 'Verifikasi Surat Sanksi Disiplin')

@section('rincian')
    @unless ($invalid ?? false)
        <div><dt>Nomor Surat</dt><dd>{{ $nomor }}</dd></div>
        <div><dt>Perihal</dt><dd>{{ $perihal }}</dd></div>
        <div><dt>Atas Nama</dt><dd>{{ $karyawan }}</dd></div>
        <div><dt>Unit</dt><dd>{{ $unit }}</dd></div>
    @endunless
@endsection

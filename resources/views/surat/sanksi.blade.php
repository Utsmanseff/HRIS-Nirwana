<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 130px 55px 70px; }
    * { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.5; }
    header { position: fixed; top: -95px; left: 0; right: 0; height: 85px; border-bottom: 2px solid #14532d; }
    header table { width: 100%; border-collapse: collapse; }
    header td { vertical-align: middle; border: none; padding: 0; }
    header .logo img { height: 46px; }
    header .identitas { text-align: center; }
    header .identitas .nama { font-size: 15px; font-weight: bold; color: #14532d; }
    header .identitas div { font-size: 9.5px; color: #555; line-height: 1.35; }
    footer { position: fixed; bottom: -45px; left: 0; right: 0; font-size: 9px; color: #777; text-align: center; }
    h1 { font-size: 13px; text-align: center; margin: 0; text-transform: uppercase; letter-spacing: .5px; }
    .nomor { text-align: center; font-size: 11px; margin: 2px 0 18px; }
    table.id { margin: 10px 0 14px; }
    table.id td { padding: 1px 0; vertical-align: top; }
    table.id td.k { width: 130px; }
    table.id td.s { width: 12px; }
    p { margin: 8px 0; text-align: justify; }
    .ttd { width: 100%; margin-top: 34px; border-collapse: collapse; }
    .ttd td { width: 33%; vertical-align: top; text-align: center; font-size: 10.5px; padding: 6px; }
    .ttd .role { font-weight: bold; }
    .ttd .space { height: 46px; }
    .ttd .nm { text-decoration: underline; font-weight: bold; }
</style>
</head>
<body>
<header>
    <table>
        <tr>
            <td class="logo" width="150"><img src="{{ public_path(config('instansi.logo')) }}" alt="{{ config('instansi.nama') }}"></td>
            <td class="identitas">
                <div class="nama">{{ config('instansi.nama_resmi') }}</div>
                <div>{{ config('instansi.alamat') }}</div>
                <div>{{ config('instansi.telp') }}</div>
                <div>{{ config('instansi.email_web') }}</div>
            </td>
        </tr>
    </table>
</header>
<footer>Dokumen resmi {{ config('instansi.nama_resmi') }}</footer>

<h1>Surat {{ $sanksi->tingkat->jenis() === 'sp' ? 'Peringatan' : 'Teguran' }} — {{ $sanksi->tingkat->label() }}</h1>
<div class="nomor">Nomor: {{ $sanksi->nomor_surat }}</div>

<p>Dengan ini pihak manajemen {{ config('instansi.nama_resmi') }} memberikan
<b>{{ $sanksi->tingkat->label() }}</b> kepada karyawan berikut:</p>

<table class="id">
    <tr><td class="k">Nama</td><td class="s">:</td><td><b>{{ $sanksi->karyawan->nama_lengkap }}</b></td></tr>
    <tr><td class="k">NIP</td><td class="s">:</td><td>{{ $sanksi->karyawan->nip }}</td></tr>
    <tr><td class="k">Jabatan</td><td class="s">:</td><td>{{ $sanksi->karyawan->jabatan?->nama ?? '-' }}</td></tr>
    <tr><td class="k">Unit</td><td class="s">:</td><td>{{ $sanksi->karyawan->orgUnit?->nama ?? '-' }}</td></tr>
</table>

<p><b>Perihal pelanggaran</b> (kejadian {{ $sanksi->tanggal_kejadian->locale('id')->translatedFormat('j F Y') }}):</p>
<p>{{ $sanksi->uraian }}</p>

<p>Surat ini berlaku selama <b>6 (enam) bulan</b>, terhitung sejak
{{ optional($sanksi->tanggal_terbit)->locale('id')->translatedFormat('j F Y') }} sampai
{{ optional($sanksi->berlaku_sampai)->locale('id')->translatedFormat('j F Y') }}. Karyawan diharapkan
memperbaiki diri; pelanggaran berulang dalam masa berlaku dapat berujung sanksi tingkat berikutnya.</p>

<table class="ttd">
    <tr>
        @foreach ($sanksi->approval as $a)
            <td>
                <div class="role">{{ ucfirst($a->peran->value) }}</div>
                <div>{{ optional($a->acted_at)->locale('id')->translatedFormat('j F Y') }}</div>
                <div class="space"></div>
                <div class="nm">{{ $a->approver->nama_lengkap }}</div>
                <div>{{ $a->approver->jabatan?->nama }}</div>
            </td>
        @endforeach
    </tr>
</table>
</body>
</html>

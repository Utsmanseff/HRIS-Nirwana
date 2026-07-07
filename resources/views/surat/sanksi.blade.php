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
    header .identitas .nama { font-size: 15px; font-weight: bold; color: #14532d; margin-bottom: 2px; }
    header .identitas div { font-size: 9.5px; color: #555; line-height: 1.35; }
    header .akreditasi { text-align: right; }
    header .akreditasi .placeholder {
        display: inline-block; width: 52px; height: 52px; line-height: 52px;
        border: 1px dashed #aab5ae; border-radius: 50%; font-size: 7px; color: #99a3a0; text-align: center;
    }
    footer { position: fixed; bottom: -45px; left: 0; right: 0; font-size: 9px; color: #777; text-align: center; }
    h1 { font-size: 13px; text-align: center; margin: 0; text-transform: uppercase; letter-spacing: .5px; }
    .nomor { text-align: center; font-size: 11px; margin: 2px 0 18px; }
    table.id { margin: 10px 0 14px; }
    table.id td { padding: 1px 0; vertical-align: top; }
    table.id td.k { width: 130px; }
    table.id td.s { width: 12px; }
    p { margin: 8px 0; text-align: justify; }
    .ttd { width: 100%; margin-top: 34px; border-collapse: collapse; }
    .ttd td { vertical-align: top; text-align: center; font-size: 10.5px; padding: 6px; }
    .ttd .role { font-weight: bold; }
    .ttd .space { height: 46px; }
    .ttd .nm { text-decoration: underline; font-weight: bold; }
    .ttd-tunggal { width: 55%; margin-left: 45%; }
    .page-break { page-break-before: always; }
    .surat-title { font-weight: bold; margin: 4px 0 14px; }
</style>
</head>
<body>
<header>
    <table>
        <tr>
            <td class="logo" width="140"><img src="{{ public_path(config('instansi.logo')) }}" alt="{{ config('instansi.nama') }}"></td>
            <td class="identitas">
                <div class="nama">{{ config('instansi.nama_resmi') }}</div>
                <div>{{ config('instansi.alamat') }}</div>
                <div>{{ config('instansi.telp') }}</div>
                <div>{{ config('instansi.email_web') }}</div>
            </td>
            <td class="akreditasi" width="70">
                @if (config('instansi.logo_akreditasi'))
                    <img src="{{ public_path(config('instansi.logo_akreditasi')) }}" style="height:52px" alt="Akreditasi">
                @else
                    <span class="placeholder">Logo<br>Akreditasi</span>
                @endif
            </td>
        </tr>
    </table>
</header>
<footer>Dokumen resmi {{ config('instansi.nama_resmi') }}</footer>
@php $tgl = fn ($d) => $d ? $d->locale('id')->translatedFormat('j F Y') : ''; @endphp

{{-- ===== HALAMAN 1: Surat Peringatan/Teguran (ttd Direktur) ===== --}}
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

<p><b>Perihal pelanggaran</b> (kejadian {{ $tgl($sanksi->tanggal_kejadian) }}):</p>
<p>{{ $sanksi->uraian }}</p>

<p>Surat ini berlaku selama <b>6 (enam) bulan</b>, terhitung sejak
{{ $tgl($sanksi->tanggal_terbit) }} sampai
{{ $tgl($sanksi->berlaku_sampai) }}. Karyawan diharapkan
memperbaiki diri; pelanggaran berulang dalam masa berlaku dapat berujung sanksi tingkat berikutnya.</p>

@if ($ttd['penerbit'])
    <table class="ttd ttd-tunggal">
        <tr>
            <td>
                <div>Mengetahui,</div>
                <div class="role">{{ $ttd['penerbit']['jabatan'] ?? 'Direktur' }}</div>
                <div>{{ $tgl($ttd['penerbit']['tanggal']) }}</div>
                <div class="space"></div>
                <div class="nm">{{ $ttd['penerbit']['nama'] }}</div>
            </td>
        </tr>
    </table>
@endif

{{-- ===== HALAMAN 2: Surat Permohonan Tindak Lanjut (ttd pengusul + Kabid) ===== --}}
@if ($ttd['pakaiHal2'])
    <div class="page-break"></div>
    <div class="surat-title">Hal: Permohonan Tindak Lanjut Pembinaan</div>
    <p>Yth. HRD {{ config('instansi.nama_resmi') }}<br>di tempat</p>
    <p>Dengan hormat, sehubungan dengan hasil pembinaan, kami mengajukan permohonan tindak
    lanjut atas nama <b>{{ $sanksi->karyawan->nama_lengkap }}</b>
    ({{ $sanksi->karyawan->jabatan?->nama ?? '-' }}). Adapun alasannya:</p>
    <p>{{ $sanksi->uraian }}</p>
    <p>Demikian permohonan ini disampaikan, atas perhatiannya kami ucapkan terima kasih.</p>

    <table class="ttd">
        <tr>
            @foreach ($ttd['pengusulChain'] as $p)
                <td style="width: {{ intdiv(100, max(count($ttd['pengusulChain']), 1)) }}%">
                    <div>{{ $loop->first ? 'Hormat kami,' : 'Mengetahui,' }}</div>
                    <div class="role">{{ $p['jabatan'] ?? $p['peran'] }}</div>
                    <div>{{ $tgl($p['tanggal']) }}</div>
                    <div class="space"></div>
                    <div class="nm">{{ $p['nama'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>
@endif
</body>
</html>

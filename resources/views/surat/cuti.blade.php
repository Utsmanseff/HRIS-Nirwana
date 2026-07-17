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
    .surat-title { font-weight: bold; margin: 4px 0 14px; text-align: center; }
    table.id { margin: 10px 0 14px; }
    table.id td { padding: 1px 0; vertical-align: top; }
    table.id td.k { width: 130px; }
    table.id td.s { width: 12px; }
    p { margin: 8px 0; text-align: justify; }
    .ttd { width: 100%; margin-top: 34px; border-collapse: collapse; }
    .ttd td { vertical-align: top; text-align: center; font-size: 10.5px; padding: 6px; }
    .ttd .role { font-weight: bold; }
    .ttd .space { height: 46px; }
    .ttd .qr { width: 78px; height: 78px; display: inline-block; }
    .ttd .qr-cap { font-size: 7.5px; color: #777; margin-top: 2px; }
    .ttd .nm { text-decoration: underline; font-weight: bold; }
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

<h1>Surat Cuti</h1>
<div class="surat-title">— {{ $pengajuan->jenisCuti->nama }} —</div>

<p>Yang bertanda tangan di bawah ini menerangkan bahwa karyawan berikut:</p>

<table class="id">
    <tr><td class="k">Nama</td><td class="s">:</td><td><b>{{ $pengajuan->karyawan->nama_lengkap }}</b></td></tr>
    <tr><td class="k">NIP</td><td class="s">:</td><td>{{ $pengajuan->karyawan->nip }}</td></tr>
    <tr><td class="k">Jabatan</td><td class="s">:</td><td>{{ $pengajuan->karyawan->jabatan?->nama ?? '-' }}</td></tr>
    <tr><td class="k">Unit</td><td class="s">:</td><td>{{ $pengajuan->karyawan->orgUnit?->nama ?? '-' }}</td></tr>
</table>

<p>Mengajukan cuti dengan rincian sebagai berikut:</p>

<table class="id">
    <tr><td class="k">Jenis Cuti</td><td class="s">:</td><td>{{ $pengajuan->jenisCuti->nama }}</td></tr>
    <tr><td class="k">Tanggal Mulai</td><td class="s">:</td><td>{{ $tgl($pengajuan->tanggal_mulai) }}</td></tr>
    <tr><td class="k">Tanggal Selesai</td><td class="s">:</td><td>{{ $tgl($pengajuan->tanggal_selesai) }}</td></tr>
    <tr><td class="k">Jumlah Hari</td><td class="s">:</td><td>{{ $pengajuan->jumlah_hari }} hari</td></tr>
    @if ($pengajuan->alasan)
    <tr><td class="k">Alasan</td><td class="s">:</td><td>{{ $pengajuan->alasan }}</td></tr>
    @endif
</table>

<p>Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya.</p>

<table class="ttd">
    <tr>
        @foreach ($ttd as $p)
            <td style="width: {{ intdiv(100, max(count($ttd), 1)) }}%">
                <div>{{ $loop->first ? 'Pemohon,' : 'Mengetahui,' }}</div>
                <div class="role">{{ $p['jabatan'] ?? $p['peran'] }}</div>
                <div>{{ $tgl($p['tanggal']) }}</div>
                @if (!empty($p['qr']))
                    <img class="qr" src="{{ $p['qr'] }}" alt="QR verifikasi">
                    <div class="qr-cap">Pindai untuk verifikasi</div>
                @else
                    <div class="space"></div>
                @endif
                <div class="nm">{{ $p['nama'] }}</div>
            </td>
        @endforeach
    </tr>
</table>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 120px 40px 60px; }
    * { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
    header { position: fixed; top: -90px; left: 0; right: 0; height: 80px; border-bottom: 2px solid #14532d; }
    header table { width: 100%; border-collapse: collapse; }
    header td { vertical-align: middle; border: none; padding: 0; }
    header .logo img { height: 42px; }
    header .identitas { font-size: 9px; color: #555; text-align: center; }
    header .identitas .nama { font-size: 13px; font-weight: bold; color: #14532d; margin-bottom: 2px; }
    header .identitas div { line-height: 1.35; }
    header .akreditasi { text-align: right; }
    header .akreditasi .placeholder {
        display: inline-block; width: 52px; height: 52px; line-height: 52px;
        border: 1px dashed #aab5ae; border-radius: 50%; font-size: 7px; color: #99a3a0; text-align: center;
    }
    footer { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 9px; color: #777; text-align: right; }
    .pagenum:after { content: counter(page); }
    h1 { font-size: 14px; margin: 0 0 2px; }
    .meta { font-size: 9.5px; color: #555; margin-bottom: 12px; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.data th { background: #f0f4f1; border: 1px solid #cfd8d2; padding: 5px 6px; text-align: left; font-size: 10px; }
    table.data td { border: 1px solid #e0e6e2; padding: 4px 6px; }
    table.data tr:nth-child(even) td { background: #fafbfa; }
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
<footer>Hal. <span class="pagenum"></span></footer>

<h1>@yield('judul')</h1>
<div class="meta">
    @yield('keterangan-filter')<br>
    Dicetak {{ now()->locale('id')->translatedFormat('j F Y H:i') }} oleh {{ auth()->user()->name }}
</div>

@yield('isi')
</body>
</html>

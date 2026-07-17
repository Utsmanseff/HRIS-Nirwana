<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Verifikasi Surat — RSU Nirwana</title>
<style>
    :root { --brand:#14532d; --ok:#14532d; --danger:#b42318; --muted:#667085; --line:#e4e7ec; --bg:#f5f7f6; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--bg); color: #1a1a1a;
        line-height: 1.5; padding: 24px 16px; -webkit-font-smoothing: antialiased; }
    .card { max-width: 460px; margin: 0 auto; background: #fff; border: 1px solid var(--line);
        border-radius: 16px; overflow: hidden; box-shadow: 0 6px 24px rgba(16,24,40,.06); }
    .head { padding: 20px 22px; border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 12px; }
    .head .logo { width: 40px; height: 40px; }
    .head h1 { font-size: 15px; color: var(--brand); }
    .head p { font-size: 12px; color: var(--muted); }
    .body { padding: 20px 22px; }
    .badge { display: inline-block; font-size: 12px; font-weight: 700; letter-spacing: .4px;
        padding: 5px 12px; border-radius: 999px; }
    .badge.ok { background: #e7f4ec; color: var(--ok); }
    .badge.danger { background: #fdeceb; color: var(--danger); }
    .badge.muted { background: #eef1f4; color: var(--muted); }
    .ket { font-size: 12.5px; color: var(--muted); margin-top: 8px; }
    dl { margin-top: 18px; }
    dl div { display: flex; justify-content: space-between; gap: 16px; padding: 9px 0; border-top: 1px solid var(--line); }
    dt { font-size: 12.5px; color: var(--muted); flex: 0 0 auto; }
    dd { font-size: 13.5px; font-weight: 600; text-align: right; }
    .ttd { margin-top: 18px; padding: 14px 16px; background: var(--bg); border-radius: 12px; }
    .ttd .lbl { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
    .ttd .nm { font-size: 14px; font-weight: 700; margin-top: 3px; }
    .ttd .meta { font-size: 12.5px; color: var(--muted); margin-top: 2px; }
    .foot { padding: 14px 22px; border-top: 1px solid var(--line); font-size: 11px; color: var(--muted); text-align: center; }
    .invalid { text-align: center; padding: 40px 24px; }
    .invalid .big { font-size: 40px; }
    .invalid h2 { font-size: 16px; margin: 12px 0 6px; }
    .invalid p { font-size: 13px; color: var(--muted); }
</style>
</head>
<body>
<div class="card">
    <div class="head">
        <img class="logo" src="{{ asset(config('instansi.logo')) }}" alt="Logo">
        <div>
            <h1>{{ config('instansi.nama_resmi') }}</h1>
            <p>Verifikasi Surat Cuti</p>
        </div>
    </div>

    @if ($invalid ?? false)
        <div class="invalid">
            <div class="big">⚠️</div>
            <h2>Dokumen tidak dikenali</h2>
            <p>Tautan verifikasi tidak valid atau sudah kedaluwarsa. Pastikan Anda memindai QR dari surat asli.</p>
        </div>
    @else
        <div class="body">
            <span class="badge {{ $status['varian'] }}">{{ $status['label'] }}</span>
            @if ($status['ket'])
                <div class="ket">{{ $status['ket'] }}</div>
            @endif

            <dl>
                <div><dt>Jenis Cuti</dt><dd>{{ $jenisCuti }}</dd></div>
                <div><dt>Tanggal</dt><dd>{{ $tanggalMulai }} – {{ $tanggalSelesai }}</dd></div>
                <div><dt>Jumlah Hari</dt><dd>{{ $jumlahHari }} hari</dd></div>
                <div><dt>Atas Nama</dt><dd>{{ $karyawan }}</dd></div>
            </dl>

            <div class="ttd">
                <div class="lbl">Ditandatangani secara elektronik</div>
                <div class="nm">{{ $penandaNama }}</div>
                <div class="meta">{{ $penandaPeran }} · {{ $penandaTanggal }}</div>
            </div>
        </div>
    @endif

    <div class="foot">Verifikasi internal {{ config('instansi.nama_resmi') }} — bukan tanda tangan tersertifikasi BSrE.</div>
</div>
</body>
</html>

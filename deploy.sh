#!/usr/bin/env bash
#
# Skrip deploy produksi HRIS Nirwana (shared hosting Hostinger, via SSH).
#
# Pemakaian di server:
#   cd ~/domains/presensi.rsunirwana.id/repo    # folder repo hasil git clone
#   ./deploy.sh
#
# Prasyarat (sekali, saat setup awal):
#   - Repo sudah di-`git clone` di server.
#   - File .env PRODUKSI sudah terpasang (APP_KEY, DB, SESSION_*, VAPID, dll).
#   - APP_KEY produksi TIDAK PERNAH diganti setelah deploy pertama.
#     (APP_KEY berubah => semua cookie session tak kebaca => semua user logout,
#      dan endpoint Livewire 404 karena prefix-nya di-hash dari APP_KEY.)
#   - Aset front-end dibangun di lokal (npm run build) lalu di-commit; server
#     TIDAK butuh Node/npm. Folder public/build ikut dilacak git.
#
# Kalau `composer` di server bukan perintah `composer`, ganti di bawah
# (mis. `composer2` atau path penuh `/usr/local/bin/composer`).

set -euo pipefail

COMPOSER_BIN="composer"

echo "==> Deploy HRIS mulai: $(date)"

# 0. Working tree server WAJIB bersih. Server tak boleh punya edit lokal —
#    kalau ada, git pull --ff-only akan gagal. Cegah sejak awal, pesan jelas.
if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "!! Ada perubahan lokal di server. Commit/buang dulu. Deploy dibatalkan."
  git status --short
  exit 1
fi

# 1. Maintenance mode: user lihat halaman tunggu, bukan error saat deploy tengah jalan.
#    trap memastikan app DINYALAKAN lagi apa pun yang terjadi (sukses / gagal).
php artisan down --retry=15 || true
trap 'php artisan up' EXIT

# 2. Tarik kode terbaru. --ff-only = maju lurus saja; menolak kalau server melenceng.
git pull --ff-only

# 3. Dependency PHP: baca composer.lock, skip paket dev, autoloader dioptimalkan.
"$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction

# 4. Migrasi: hanya migrasi yang BELUM tercatat di tabel `migrations` yang jalan.
#    --force = lewati konfirmasi "Application In Production!" (skrip non-interaktif).
php artisan migrate --force

# 5. Rebuild cache. Clear dulu supaya cache lama (config/.env/route/view) tak nyangkut,
#    lalu cache ulang dengan nilai terkini. Urutan penting untuk Livewire 4.
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. app dinyalakan lagi oleh trap EXIT di atas.
echo "==> Deploy HRIS selesai: $(date)"

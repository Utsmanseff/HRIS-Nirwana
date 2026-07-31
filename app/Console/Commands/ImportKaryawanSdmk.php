<?php

namespace App\Console\Commands;

use App\Models\Karyawan;
use App\Support\ResolveJabatanDariNama;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('import:karyawan-sdmk {file? : Path ke file CSV cleaned.csv}')]
#[Description('Import data karyawan dari file cleaned.csv SDMK')]
class ImportKaryawanSdmk extends Command
{
    public function handle(): int
    {
        $filePath = $this->argument('file') ?? storage_path('app/import-sdmk/cleaned.csv');

        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");

            return self::FAILURE;
        }

        $fp = fopen($filePath, 'r');
        if (! $fp) {
            $this->error("Gagal membuka file: {$filePath}");

            return self::FAILURE;
        }

        $header = fgetcsv($fp);
        if (! $header) {
            $this->error('File CSV kosong atau header tidak valid.');
            fclose($fp);

            return self::FAILURE;
        }

        // Clean UTF-8 BOM if present
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        $total = 0;
        $berhasilBaru = 0;
        $berhasilUpdate = 0;
        $gagalCount = 0;
        $errors = [];

        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) < count($header)) {
                continue;
            }

            $data = array_combine($header, $row);
            $total++;

            try {
                DB::transaction(function () use ($data, &$berhasilBaru, &$berhasilUpdate) {
                    $unitKerja = $data['unit_kerja'] ?? '';
                    $jabatanNama = $data['jabatan'] ?? '';

                    $jabatan = ResolveJabatanDariNama::resolve($unitKerja, $jabatanNama);

                    $nip = trim($data['nip'] ?? '');
                    $namaLengkap = trim($data['nama_lengkap'] ?? '');

                    if ($nip === '') {
                        throw new \InvalidArgumentException("NIP kosong untuk karyawan {$namaLengkap}");
                    }
                    if ($namaLengkap === '') {
                        throw new \InvalidArgumentException("Nama lengkap kosong untuk NIP {$nip}");
                    }

                    $existing = Karyawan::where('nip', $nip)->first();

                    $payload = [
                        'nip' => $nip,
                        'nik' => ! empty($data['nik']) ? trim($data['nik']) : null,
                        'nama_lengkap' => $namaLengkap,
                        'jabatan_id' => $jabatan->id,
                        'org_unit_id' => $jabatan->org_unit_id,
                        'jenis_kelamin' => ! empty($data['jenis_kelamin']) ? trim($data['jenis_kelamin']) : null,
                        'tempat_lahir' => ! empty($data['tempat_lahir']) ? trim($data['tempat_lahir']) : null,
                        'tanggal_lahir' => ! empty($data['tanggal_lahir']) ? trim($data['tanggal_lahir']) : null,
                        'alamat' => ! empty($data['alamat']) ? trim($data['alamat']) : null,
                        'no_hp' => ! empty($data['no_hp']) ? trim($data['no_hp']) : null,
                        'email' => ! empty($data['email']) ? trim($data['email']) : null,
                        'pendidikan_terakhir' => ! empty($data['pendidikan_terakhir']) ? trim($data['pendidikan_terakhir']) : null,
                        'tanggal_masuk' => ! empty($data['tanggal_masuk']) ? trim($data['tanggal_masuk']) : null,
                        'status' => ! empty($data['status']) ? trim($data['status']) : 'aktif',
                    ];

                    if ($existing) {
                        $existing->update($payload);
                        $berhasilUpdate++;
                    } else {
                        Karyawan::create($payload);
                        $berhasilBaru++;
                    }
                });
            } catch (\Throwable $e) {
                $gagalCount++;
                $errors[] = "Baris {$total} ({$data['nama_lengkap']}): ".$e->getMessage();
            }
        }

        fclose($fp);

        $this->info('Import SDMK Selesai.');
        $this->line("Total data processed : {$total}");
        $this->line("Berhasil dibuat baru : {$berhasilBaru}");
        $this->line("Berhasil di-update  : {$berhasilUpdate}");
        $this->line("Gagal                : {$gagalCount}");

        if ($gagalCount > 0) {
            $this->warn('Detail Kegagalan:');
            foreach ($errors as $err) {
                $this->error(" - {$err}");
            }
        }

        return self::SUCCESS;
    }
}

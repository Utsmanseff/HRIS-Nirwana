<?php

// scripts/clean-sdmk.php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

function cleanSdmkData(?string $inputFile = null, ?string $outputDir = null): array
{
    $inputFile = $inputFile ?? __DIR__ . '/../storage/app/import-sdmk/SDMK Juli 2026.xlsx';
    $outputDir = $outputDir ?? __DIR__ . '/../storage/app/import-sdmk';

    if (!file_exists($inputFile)) {
        throw new RuntimeException("Input file not found: {$inputFile}");
    }

    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    $spreadsheet = IOFactory::load($inputFile);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    $cleanedRows = [];
    $manualNotes = [];

    // Header starts row 1 & 2. Data starts at row 3.
    for ($r = 3; $r <= $highestRow; $r++) {
        $noCell = $sheet->getCell([1, $r])->getCalculatedValue();
        $unitRaw = $sheet->getCell([2, $r])->getValue();
        $nikRaw = $sheet->getCell([3, $r])->getValue();
        $kodePegRaw = $sheet->getCell([4, $r])->getValue();
        $tglMasukRaw = $sheet->getCell([5, $r])->getValue();
        // Col 6: Masa kerja (skip)
        // Col 7: Golongan (skip task 1)
        $jabatanRaw = $sheet->getCell([8, $r])->getValue();
        $namaRaw = $sheet->getCell([9, $r])->getValue();
        $jkRaw = $sheet->getCell([10, $r])->getValue();
        $alamatRaw = $sheet->getCell([11, $r])->getValue();
        $tempatLahirRaw = $sheet->getCell([12, $r])->getValue();
        $tglLahirRaw = $sheet->getCell([13, $r])->getValue();
        // Col 14: Usia (skip)
        $prodiRaw = $sheet->getCell([15, $r])->getValue();
        $jenjangRaw = $sheet->getCell([16, $r])->getValue();
        $tahunLulusRaw = $sheet->getCell([17, $r])->getValue();
        $sekolahRaw = $sheet->getCell([18, $r])->getValue();
        $hpRaw = $sheet->getCell([19, $r])->getValue();
        $emailRaw = $sheet->getCell([20, $r])->getValue();

        $nama = trim((string)$namaRaw);
        $unit = normalizeText((string)$unitRaw);
        $jabatan = normalizeText((string)$jabatanRaw);

        // Skip rows without name and unit (empty trailing rows)
        if ($nama === '' && $unit === '') {
            continue;
        }

        $no = is_numeric($noCell) ? (int)$noCell : (int)preg_replace('/\D/', '', (string)$noCell);

        // 1. NIP / Kode Pegawai
        $nip = trim((string)$kodePegRaw);
        if ($nip === '') {
            $nip = 'TEMP-' . ($no > 0 ? $no : $r);
            $manualNotes[] = [
                'no' => $no,
                'nama_lengkap' => $nama,
                'field' => 'nip',
                'issue' => 'Kode Pegawai (NIP) kosong di Excel',
                'detail' => "Di-generate placeholder '{$nip}'",
            ];
        }

        // 2. NIK
        $nik = trim((string)$nikRaw);
        if ($nik !== '' && is_numeric($nik)) {
            $nik = sprintf('%016d', (float)$nik);
        }

        // 3. Jenis Kelamin
        $jkStr = strtoupper(trim((string)$jkRaw));
        if (str_contains($jkStr, 'LAKI') || $jkStr === 'L') {
            $jenisKelamin = 'L';
        } elseif (str_contains($jkStr, 'PEREMPUAN') || $jkStr === 'P') {
            $jenisKelamin = 'P';
        } else {
            $jenisKelamin = null;
        }

        // 4. Tanggal Lahir
        $tanggalLahir = parseExcelDate($sheet->getCell([13, $r]), $tglLahirRaw);
        if ($tglLahirRaw !== null && (string)$tglLahirRaw !== '' && $tanggalLahir === null) {
            $manualNotes[] = [
                'no' => $no,
                'nama_lengkap' => $nama,
                'field' => 'tanggal_lahir',
                'issue' => 'Gagal parse format tanggal lahir',
                'detail' => "Nilai mentah: '" . (string)$tglLahirRaw . "'",
            ];
        }

        // 5. Tanggal Masuk
        $tanggalMasuk = parseExcelDate($sheet->getCell([5, $r]), $tglMasukRaw);
        if ($tglMasukRaw !== null && (string)$tglMasukRaw !== '' && $tanggalMasuk === null) {
            $manualNotes[] = [
                'no' => $no,
                'nama_lengkap' => $nama,
                'field' => 'tanggal_masuk',
                'issue' => 'Gagal parse format tanggal masuk',
                'detail' => "Nilai mentah: '" . (string)$tglMasukRaw . "'",
            ];
        }

        // 6. Tempat Lahir & Alamat
        $tempatLahir = trim((string)$tempatLahirRaw) ?: null;
        $alamat = trim((string)$alamatRaw) ?: null;

        // Check missing bio info
        if (empty($tempatLahir) && empty($tanggalLahir) && empty($alamat)) {
            $manualNotes[] = [
                'no' => $no,
                'nama_lengkap' => $nama,
                'field' => 'biodata',
                'issue' => 'Alamat, tempat lahir, dan tanggal lahir kosong',
                'detail' => 'Butuh konfirmasi data dari HR',
            ];
        }

        // 7. No HP
        $hpInput = trim((string)$hpRaw);
        $noHp = null;
        if ($hpInput !== '') {
            if (str_contains($hpInput, '/')) {
                $hpParts = explode('/', $hpInput);
                $noHp = trim($hpParts[0]);
                $secondHp = trim($hpParts[1]);
                $manualNotes[] = [
                    'no' => $no,
                    'nama_lengkap' => $nama,
                    'field' => 'no_hp',
                    'issue' => 'Nomor HP ganda di Excel',
                    'detail' => "Disimpan nomor pertama '{$noHp}', nomor kedua: '{$secondHp}'",
                ];
            } else {
                $noHp = $hpInput;
            }
        }

        // 8. Email
        $emailInput = trim((string)$emailRaw);
        $email = null;
        if ($emailInput !== '') {
            if (filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
                $email = $emailInput;
            } else {
                $manualNotes[] = [
                    'no' => $no,
                    'nama_lengkap' => $nama,
                    'field' => 'email',
                    'issue' => 'Format email rusak',
                    'detail' => "Email mentah '{$emailInput}' diisi null",
                ];
            }
        }

        // 9. Pendidikan Terakhir
        $pendidikanTerakhir = formatPendidikan($jenjangRaw, $prodiRaw, $sekolahRaw, $tahunLulusRaw);

        $cleanedRows[] = [
            'nip' => $nip,
            'nik' => $nik,
            'nama_lengkap' => $nama,
            'unit_kerja' => $unit,
            'jabatan' => $jabatan,
            'jenis_kelamin' => $jenisKelamin,
            'tempat_lahir' => $tempatLahir,
            'tanggal_lahir' => $tanggalLahir,
            'alamat' => $alamat,
            'no_hp' => $noHp,
            'email' => $email,
            'pendidikan_terakhir' => $pendidikanTerakhir,
            'tanggal_masuk' => $tanggalMasuk,
            'status' => 'aktif',
        ];
    }

    // Write CSV cleaned.csv
    $cleanedCsvPath = $outputDir . '/cleaned.csv';
    $fpCleaned = fopen($cleanedCsvPath, 'w');
    if (!empty($cleanedRows)) {
        fputcsv($fpCleaned, array_keys($cleanedRows[0]));
        foreach ($cleanedRows as $row) {
            fputcsv($fpCleaned, $row);
        }
    }
    fclose($fpCleaned);

    // Write CSV catatan-manual.csv
    $manualCsvPath = $outputDir . '/catatan-manual.csv';
    $fpManual = fopen($manualCsvPath, 'w');
    fputcsv($fpManual, ['no', 'nama_lengkap', 'field', 'issue', 'detail']);
    foreach ($manualNotes as $note) {
        fputcsv($fpManual, $note);
    }
    fclose($fpManual);

    return [
        'cleaned_count' => count($cleanedRows),
        'manual_notes_count' => count($manualNotes),
        'cleaned_csv' => $cleanedCsvPath,
        'catatan_manual_csv' => $manualCsvPath,
    ];
}

function normalizeText(string $val): string
{
    return trim(preg_replace('/\s+/', ' ', $val));
}

function parseExcelDate(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell, mixed $rawVal): ?string
{
    if ($rawVal === null || (string)$rawVal === '') {
        return null;
    }

    // Check if cell is formatted as numeric Excel date
    if (is_numeric($rawVal)) {
        try {
            $dt = ExcelDate::excelToDateTimeObject((float)$rawVal);
            return $dt->format('Y-m-d');
        } catch (\Throwable $e) {
            // fallback to string parse
        }
    }

    $strVal = trim((string)$rawVal);
    
    // dd/mm/yyyy or dd-mm-yyyy or d/m/Y or d-m-Y
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $strVal, $matches)) {
        $day = (int)$matches[1];
        $month = (int)$matches[2];
        $year = (int)$matches[3];
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    // yyyy-mm-dd or yyyy/mm/dd
    if (preg_match('/^(\d{4})[\/\-](\d{2})[\/\-](\d{2})$/', $strVal, $matches)) {
        return sprintf('%04d-%02d-%02d', (int)$matches[1], (int)$matches[2], (int)$matches[3]);
    }

    return null;
}

function formatPendidikan(mixed $jenjangRaw, mixed $prodiRaw, mixed $sekolahRaw, mixed $tahunRaw): ?string
{
    $jenjang = normalizeText((string)$jenjangRaw);
    $prodi = normalizeText((string)$prodiRaw);
    $sekolah = normalizeText((string)$sekolahRaw);
    $tahun = normalizeText((string)$tahunRaw);

    $studiParts = array_filter([$jenjang, $prodi]);
    $studi = implode(' ', $studiParts);

    $inst = $sekolah;
    if ($tahun !== '') {
        $inst = $inst !== '' ? "{$inst} ({$tahun})" : "({$tahun})";
    }

    if ($studi !== '' && $inst !== '') {
        return "{$studi} — {$inst}";
    }

    return $studi !== '' ? $studi : ($inst !== '' ? $inst : null);
}

if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    echo "Running clean-sdmk.php...\n";
    $result = cleanSdmkData();
    echo "Cleaned rows: {$result['cleaned_count']}\n";
    echo "Manual notes: {$result['manual_notes_count']}\n";
    echo "Files saved to {$result['cleaned_csv']} and {$result['catatan_manual_csv']}\n";
}

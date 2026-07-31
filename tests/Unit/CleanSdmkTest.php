<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../scripts/clean-sdmk.php';

class CleanSdmkTest extends TestCase
{
    private string $cleanedCsvPath;
    private string $catatanManualCsvPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cleanedCsvPath = __DIR__ . '/../../storage/app/import-sdmk/cleaned.csv';
        $this->catatanManualCsvPath = __DIR__ . '/../../storage/app/import-sdmk/catatan-manual.csv';

        // Ensure cleaning script runs and outputs are available
        if (!file_exists($this->cleanedCsvPath) || !file_exists($this->catatanManualCsvPath)) {
            cleanSdmkData();
        }
    }

    public function test_sdmk_cleaning_produces_174_cleaned_rows(): void
    {
        $result = cleanSdmkData();
        $this->assertSame(174, $result['cleaned_count']);

        $rows = array_map('str_getcsv', file($this->cleanedCsvPath));
        $header = array_shift($rows);

        $this->assertCount(174, $rows);
        $this->assertContains('nip', $header);
        $this->assertContains('nama_lengkap', $header);
        $this->assertContains('tanggal_lahir', $header);
        $this->assertContains('tanggal_masuk', $header);
    }

    public function test_sdmk_cleaning_generates_placeholder_nip_for_row_5(): void
    {
        $rows = array_map('str_getcsv', file($this->cleanedCsvPath));
        $header = array_shift($rows);
        $nipIdx = array_search('nip', $header);
        $namaIdx = array_search('nama_lengkap', $header);

        $row5 = null;
        foreach ($rows as $row) {
            if (str_contains($row[$namaIdx], 'M. Suryana Rachman')) {
                $row5 = $row;
                break;
            }
        }

        $this->assertNotNull($row5);
        $this->assertSame('TEMP-5', $row5[$nipIdx]);
    }

    public function test_sdmk_cleaning_formats_dates_consistently_as_yyyy_mm_dd(): void
    {
        $rows = array_map('str_getcsv', file($this->cleanedCsvPath));
        $header = array_shift($rows);
        $tglLahirIdx = array_search('tanggal_lahir', $header);
        $tglMasukIdx = array_search('tanggal_masuk', $header);

        foreach ($rows as $row) {
            $tglLahir = $row[$tglLahirIdx];
            if (!empty($tglLahir)) {
                $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $tglLahir);
            }

            $tglMasuk = $row[$tglMasukIdx];
            if (!empty($tglMasuk)) {
                $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $tglMasuk);
            }
        }
    }

    public function test_sdmk_cleaning_logs_damaged_emails_to_catatan_manual_and_sets_email_to_null(): void
    {
        $manualNotes = array_map('str_getcsv', file($this->catatanManualCsvPath));
        $manualHeader = array_shift($manualNotes);
        $issueIdx = array_search('issue', $manualHeader);
        $fieldIdx = array_search('field', $manualHeader);
        $detailIdx = array_search('detail', $manualHeader);

        $emailIssues = array_filter($manualNotes, fn($row) => $row[$fieldIdx] === 'email');
        $this->assertGreaterThanOrEqual(2, count($emailIssues));

        $detailsCombined = implode(' ', array_column($emailIssues, $detailIdx));
        $this->assertStringContainsString('bardianahmad@gmail,com', $detailsCombined);
        $this->assertStringContainsString('nrl_sholehah@', $detailsCombined);

        // Verify in cleaned.csv that their email fields are empty/null
        $cleanedRows = array_map('str_getcsv', file($this->cleanedCsvPath));
        $cleanedHeader = array_shift($cleanedRows);
        $namaIdx = array_search('nama_lengkap', $cleanedHeader);
        $emailIdx = array_search('email', $cleanedHeader);

        foreach ($cleanedRows as $row) {
            if (str_contains($row[$namaIdx], 'Ahmad Bardian') || str_contains($row[$namaIdx], 'Nurul Sholehah')) {
                $this->assertEmpty($row[$emailIdx]);
            }
        }
    }
}

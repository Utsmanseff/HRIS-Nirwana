<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\View;

/**
 * Memotong SATU bagian dari view bab panduan supaya bisa ditampilkan di sheet
 * tombol "?" tanpa menduplikasi teks panduan. Sumber teks tetap satu:
 * resources/views/panduan/{bab}.blade.php.
 */
class FragmenPanduan
{
    /**
     * @return array{judul:string,html:string,lain:list<array{id:string,judul:string}>}|null
     */
    public static function ambil(string $bab, string $id): ?array
    {
        // Id bagian selalu kebab-case (lihat komponen panduan.bagian). Menolak selain
        // itu sekaligus menutup celah injeksi ke ekspresi XPath di bawah.
        if (! preg_match('/^[a-z0-9-]+$/', $id)) {
            return null;
        }

        $meta = Panduan::cari($bab);

        if ($meta === null || ! View::exists('panduan.'.$bab)) {
            return null;
        }

        $html = view('panduan.'.$bab, [
            'meta' => $meta,
            'tetangga' => Panduan::tetangga($bab),
        ])->render();

        $doc = new DOMDocument();
        $sebelumnya = libxml_use_internal_errors(true);
        // Prolog XML memaksa DOMDocument membaca sebagai UTF-8; tanpa itu huruf
        // beraksen dan tanda panah di teks panduan jadi mojibake.
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($sebelumnya);

        $xp = new DOMXPath($doc);

        $lain = [];
        foreach ($xp->query('//section[@data-bagian]') as $section) {
            $h2 = $xp->query('.//h2', $section)->item(0);
            $lain[] = [
                'id' => $section->getAttribute('data-bagian'),
                'judul' => $h2 ? trim($h2->textContent) : '',
            ];
        }

        $node = $xp->query("//section[@data-bagian='".$id."']")->item(0);

        if ($node === null) {
            return null;
        }

        $h2 = $xp->query('.//h2', $node)->item(0);
        $judul = $h2 ? trim($h2->textContent) : $meta['judul'];

        // Buang seluruh baris judul (h2 + tautan "↑ Bagian atas"): di dalam sheet,
        // judulnya sudah ada di kepala dan "bagian atas" tak punya arti.
        if ($h2 !== null && $h2->parentNode !== null) {
            $baris = $h2->parentNode;
            $baris->parentNode->removeChild($baris);
        }

        return [
            'judul' => $judul,
            'html' => trim($doc->saveHTML($node)),
            'lain' => $lain,
        ];
    }
}

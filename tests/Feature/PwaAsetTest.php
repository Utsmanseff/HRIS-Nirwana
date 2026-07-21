<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaAsetTest extends TestCase
{
    public function test_manifest_memakai_nama_nirwanahris(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('NirwanaHRIS', $manifest['name']);
        $this->assertSame('NirwanaHRIS', $manifest['short_name']);
        $this->assertSame('#0c1312', $manifest['background_color']);
    }
}

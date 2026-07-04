<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Livewire\Sdm\KaryawanDetail;
use App\Models\Dokumen;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class KaryawanDokumenTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_upload_pdf_disimpan_apa_adanya(): void
    {
        Storage::fake('local');
        $kar = Karyawan::factory()->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanDetail::class, ['karyawan' => $kar])
            ->set('tipeDokumen', 'ijazah')
            ->set('berkas', UploadedFile::fake()->create('Ijazah S1.pdf', 500, 'application/pdf'))
            ->call('unggahDokumen')
            ->assertHasNoErrors();

        $dok = Dokumen::where('karyawan_id', $kar->id)->first();
        $this->assertNotNull($dok);
        $this->assertSame('application/pdf', $dok->mime);
        $this->assertStringEndsWith('.pdf', $dok->path);
        Storage::disk('local')->assertExists($dok->path);
    }

    public function test_upload_gambar_dikonversi_webp(): void
    {
        Storage::fake('local');
        $kar = Karyawan::factory()->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanDetail::class, ['karyawan' => $kar])
            ->set('tipeDokumen', 'ktp')
            ->set('berkas', UploadedFile::fake()->image('ktp.png', 100, 60))
            ->call('unggahDokumen')
            ->assertHasNoErrors();

        $dok = Dokumen::where('karyawan_id', $kar->id)->first();
        $this->assertSame('image/webp', $dok->mime);
        $this->assertStringEndsWith('.webp', $dok->path);
        Storage::disk('local')->assertExists($dok->path);
    }

    public function test_tipe_dokumen_wajib(): void
    {
        Storage::fake('local');
        $kar = Karyawan::factory()->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanDetail::class, ['karyawan' => $kar])
            ->set('tipeDokumen', '')
            ->set('berkas', UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))
            ->call('unggahDokumen')
            ->assertHasErrors(['tipeDokumen']);
    }

    public function test_unduh_dokumen_gated(): void
    {
        Storage::fake('local');
        $kar = Karyawan::factory()->create();
        Storage::disk('local')->put('dokumen/'.$kar->id.'/tes.pdf', '%PDF-1.4 tes');
        $dok = Dokumen::create(['karyawan_id' => $kar->id, 'tipe' => 'lainnya', 'path' => 'dokumen/'.$kar->id.'/tes.pdf', 'mime' => 'application/pdf', 'ukuran' => 12]);

        $this->actingAs($this->userSdm())->get('/sdm/dokumen/'.$dok->id)->assertOk();

        $tanpaAkses = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $this->actingAs($tanpaAkses)->get('/sdm/dokumen/'.$dok->id)->assertForbidden();
    }

    public function test_lihat_dokumen_inline_gated(): void
    {
        Storage::fake('local');
        $kar = Karyawan::factory()->create();
        Storage::disk('local')->put('dokumen/'.$kar->id.'/foto.webp', 'RIFF....WEBP');
        $dok = Dokumen::create([
            'karyawan_id' => $kar->id, 'tipe' => 'ktp',
            'path' => 'dokumen/'.$kar->id.'/foto.webp', 'mime' => 'image/webp', 'ukuran' => 12,
        ]);

        $res = $this->actingAs($this->userSdm())->get('/sdm/dokumen/'.$dok->id.'/lihat');
        $res->assertOk();
        $this->assertStringContainsString('inline', (string) $res->headers->get('content-disposition'));

        $tanpaAkses = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $this->actingAs($tanpaAkses)->get('/sdm/dokumen/'.$dok->id.'/lihat')->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Inventaris;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Livewire\Inventaris\AsetDetail;
use App\Models\Aset;
use App\Models\KategoriInventaris;
use App\Models\Karyawan;
use App\Models\LampiranAset;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LampiranAsetTest extends TestCase
{
    use RefreshDatabase;

    private function userIt(): User
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $u->assignRole(Role::It->value);

        return $u;
    }

    private function asetIt(): Aset
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);

        return Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);
    }

    public function test_upload_pdf_tersimpan(): void
    {
        Storage::fake('local');
        $aset = $this->asetIt();

        Livewire::actingAs($this->userIt())
            ->test(AsetDetail::class, ['aset' => $aset])
            ->set('lampiranTipe', 'faktur')
            ->set('berkas', UploadedFile::fake()->create('faktur.pdf', 40, 'application/pdf'))
            ->call('simpanLampiran')
            ->assertHasNoErrors();

        $l = LampiranAset::where('aset_id', $aset->id)->first();
        $this->assertNotNull($l);
        $this->assertSame('application/pdf', $l->mime);
        Storage::disk('local')->assertExists($l->path);
    }

    public function test_upload_gambar_jadi_webp(): void
    {
        Storage::fake('local');
        $aset = $this->asetIt();

        Livewire::actingAs($this->userIt())
            ->test(AsetDetail::class, ['aset' => $aset])
            ->set('lampiranTipe', 'manual')
            ->set('berkas', UploadedFile::fake()->image('foto.jpg', 800, 600))
            ->call('simpanLampiran')
            ->assertHasNoErrors();

        $l = LampiranAset::where('aset_id', $aset->id)->first();
        $this->assertSame('image/webp', $l->mime);
        $this->assertStringEndsWith('.webp', $l->path);
        Storage::disk('local')->assertExists($l->path);
    }

    public function test_hapus_lampiran(): void
    {
        Storage::fake('local');
        $aset = $this->asetIt();
        Storage::disk('local')->put('aset/'.$aset->id.'/x.pdf', 'data');
        $l = LampiranAset::create(['aset_id' => $aset->id, 'tipe' => 'faktur', 'path' => 'aset/'.$aset->id.'/x.pdf', 'mime' => 'application/pdf']);

        Livewire::actingAs($this->userIt())
            ->test(AsetDetail::class, ['aset' => $aset])
            ->call('hapusLampiran', $l->id);

        $this->assertDatabaseMissing('lampiran_aset', ['id' => $l->id]);
        Storage::disk('local')->assertMissing('aset/'.$aset->id.'/x.pdf');
    }

    public function test_controller_stream_untuk_tim_sendiri(): void
    {
        Storage::fake('local');
        $aset = $this->asetIt();
        Storage::disk('local')->put('aset/'.$aset->id.'/x.pdf', 'data');
        $l = LampiranAset::create(['aset_id' => $aset->id, 'tipe' => 'faktur', 'path' => 'aset/'.$aset->id.'/x.pdf', 'mime' => 'application/pdf']);

        $this->actingAs($this->userIt())
            ->get(route('inventaris.lampiran', $l))
            ->assertOk();
    }

    public function test_controller_tolak_tim_lain(): void
    {
        Storage::fake('local');
        $katAtem = KategoriInventaris::factory()->create(['tim' => TimTeknis::Atem]);
        $asetAtem = Aset::factory()->create(['kategori_inventaris_id' => $katAtem->id]);
        Storage::disk('local')->put('aset/'.$asetAtem->id.'/x.pdf', 'data');
        $l = LampiranAset::create(['aset_id' => $asetAtem->id, 'tipe' => 'faktur', 'path' => 'aset/'.$asetAtem->id.'/x.pdf', 'mime' => 'application/pdf']);

        $this->actingAs($this->userIt())
            ->get(route('inventaris.lampiran', $l))
            ->assertForbidden();
    }
}

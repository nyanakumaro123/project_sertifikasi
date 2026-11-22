<?php

namespace Tests\Feature;

use App\Models\Garasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Use case "Buat Garasi Berkelompok": ID otomatis, harga isi listrik, batas 999.
 */
class GarasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    private function buatBatch(array $data): TestResponse
    {
        return $this->from(route('garasi.create'))
            ->post(route('garasi.store-batch'), $data);
    }

    private function siapkanGarasi(string $id, string $tipe = 'Sepeda'): Garasi
    {
        return Garasi::create([
            'id'            => $id,
            'tipe_garasi'   => $tipe,
            'harga_harian'  => 10000,
            'status_garasi' => 'available',
        ]);
    }

    public function test_admin_membuat_dua_garasi_sepeda_dengan_id_berurutan(): void
    {
        $this->buatBatch(['tipe_garasi' => 'Sepeda', 'jumlah' => 2, 'harga_harian' => 10000])
            ->assertRedirect(route('garasi.index'))
            ->assertSessionHas('status', '2 garasi Sepeda berhasil dibuat (ID S001 sampai S002).');

        $this->assertSame(['S001', 'S002'], Garasi::orderBy('id')->pluck('id')->all());

        $garasi = Garasi::find('S001');
        $this->assertSame('Sepeda', $garasi->tipe_garasi);
        $this->assertSame('available', $garasi->status_garasi);
        $this->assertEquals(10000, $garasi->harga_harian);
        $this->assertNull($garasi->harga_isi_listrik_per_kwh);
    }

    public function test_admin_membuat_dua_garasi_mobil_listrik_dengan_harga_isi_listrik(): void
    {
        $this->buatBatch([
            'tipe_garasi'               => 'Mobil Listrik',
            'jumlah'                    => 2,
            'harga_harian'              => 100000,
            'harga_isi_listrik_per_kwh' => 2000,
        ])->assertRedirect(route('garasi.index'))
          ->assertSessionHas('status', '2 garasi Mobil Listrik berhasil dibuat (ID E001 sampai E002).');

        $this->assertSame(['E001', 'E002'], Garasi::orderBy('id')->pluck('id')->all());
        $this->assertEquals(100000, Garasi::find('E001')->harga_harian);
        $this->assertEquals(2000, Garasi::find('E001')->harga_isi_listrik_per_kwh);
        $this->assertEquals(2000, Garasi::find('E002')->harga_isi_listrik_per_kwh);
    }

    public function test_jumlah_lebih_dari_999_ditolak_oleh_validasi(): void
    {
        $this->buatBatch(['tipe_garasi' => 'Mobil', 'jumlah' => 1000, 'harga_harian' => 15000])
            ->assertSessionHasErrors(['jumlah' => 'Jumlah garasi maksimal 999 unit.']);

        $this->assertSame(0, Garasi::count());
    }

    public function test_penambahan_melebihi_sisa_kuota_ditolak(): void
    {
        $this->siapkanGarasi('S990');

        $this->buatBatch(['tipe_garasi' => 'Sepeda', 'jumlah' => 10, 'harga_harian' => 10000])
            ->assertSessionHasErrors('jumlah');

        $pesan = session('errors')->first('jumlah');
        $this->assertStringContainsString('melebihi batas maksimal 999 unit', $pesan);
        $this->assertStringContainsString('sisa kuota 9 unit', $pesan);

        $this->assertSame(1, Garasi::count());
    }

    public function test_daftar_garasi_menampilkan_data(): void
    {
        $this->get(route('garasi.index'))
            ->assertOk()
            ->assertViewIs('garasi.garasiList')
            ->assertSee('Belum ada garasi');

        $this->siapkanGarasi('S001');

        $this->get(route('garasi.index'))
            ->assertOk()
            ->assertSee('S001')
            ->assertSee('Tersedia');
    }
}
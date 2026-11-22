<?php

namespace Tests\Feature;

use App\Models\Garasi;
use App\Models\InformasiSewa;
use App\Models\PerpanjangSewa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Use case "Sewa Garasi", "Lihat Daftar Sewa Garasi", dan "Perpanjang Sewa Garasi".
 */
class SewaTest extends TestCase
{
    use RefreshDatabase;

    private User $penyewa;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Hari ini diset: Minggu, 22 November 2026
        Carbon::setTestNow(Carbon::create(2026, 11, 22, 10, 0, 0));

        $this->penyewa = User::factory()->create();
        $this->actingAs($this->penyewa);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // bagian helpernya

    private function garasi(
        string $id,
        string $tipe = 'Sepeda',
        float $harga = 10000,
        ?float $listrik = null,
        string $status = 'available'
    ): Garasi {
        return Garasi::create([
            'id'                        => $id,
            'tipe_garasi'               => $tipe,
            'harga_harian'              => $harga,
            'harga_isi_listrik_per_kwh' => $listrik,
            'status_garasi'             => $status,
        ]);
    }

    private function buatSewa(Garasi $garasi, User $user, string $mulai, string $akhir, array $lain = []): InformasiSewa
    {
        return InformasiSewa::create(array_merge([
            'user_id'            => $user->id,
            'garasi_id'          => $garasi->id,
            'tipe_sewa'          => 'Harian',
            'model_kendaraan'    => 'Heist',
            'merek_kendaraan'    => 'Polygon',
            'jenis_kendaraan'    => 'Sepeda',
            'tanggal_mulai_sewa' => $mulai,
            'tanggal_akhir_sewa' => $akhir,
            'total_harga'        => 30000,
            'bukti_bayar_url'    => 'http://127.0.0.1:8000/storage/bukti-bayar/contoh.jpg',
        ], $lain));
    }

    private function kirim(array $ubah = [], array $hapus = []): TestResponse
    {
        $data = array_merge([
            'jenis_kendaraan'  => 'Sepeda',
            'merek_kendaraan'  => 'Polygon',
            'model_kendaraan'  => 'Heist',
            'tipe_garasi'      => 'Sepeda',
            'garasi_id'        => 'S001',
            'tipe_sewa'        => 'Harian',
            'jumlah_hari_sewa' => 3,
            'bukti_bayar'      => UploadedFile::fake()->image('bukti.jpg'),
        ], $ubah);

        return $this->from(route('sewa.index'))
            ->post(route('sewa.store'), Arr::except($data, $hapus));
    }

    // Bawaan: Mobil Listrik di garasi E001, baterai 50 kWh, awal 20 kWh.
    private function kirimListrik(array $ubah = [], array $hapus = []): TestResponse
    {
        return $this->kirim(array_merge([
            'jenis_kendaraan'       => 'Mobil Listrik',
            'plat_nomor'            => '12345',
            'merek_kendaraan'       => 'daihatsu',
            'model_kendaraan'       => 'txcp',
            'tipe_garasi'           => 'Mobil Listrik',
            'garasi_id'             => 'E001',
            'kapasitas_baterai_kwh' => 50,
            'kwh_awal'              => 20,
        ], $ubah), $hapus);
    }

    private function kirimPerpanjang(int $sewaId, array $ubah = []): TestResponse
    {
        $data = array_merge([
            'tanggal_akhir_baru'     => '2026-11-30',
            'bukti_bayar_perpanjang' => UploadedFile::fake()->image('bukti.png'),
        ], $ubah);

        return $this->post(route('sewa.extend.store', ['rentalId' => $sewaId]), $data);
    }

    private function masukSebagaiAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
    }

    // tes bagian sewa garasi di sisi penyewa sama perpanjangan admin

    public function test_penyewa_menyewa_mobil_listrik_bulanan_dengan_total_3_5_juta(): void
    {
        $this->garasi('E001', 'Mobil Listrik', 100000, 2000);

        $this->kirimListrik(
            ['tipe_sewa' => 'Bulanan', 'kapasitas_baterai_kwh' => 1000, 'kwh_awal' => 500],
            ['jumlah_hari_sewa']
        )->assertRedirect(route('sewa.index'))
         ->assertSessionHas('status', 'Sewa garasi E001 berhasil sampai 21/12/2026. Total biaya: Rp 3.500.000.')
         ->assertSessionHasNoErrors();

        $sewa = InformasiSewa::first();
        $this->assertSame('Bulanan', $sewa->tipe_sewa);
        $this->assertNull($sewa->jumlah_hari_sewa);
        $this->assertSame('2026-11-22', $sewa->tanggal_mulai_sewa->toDateString());
        $this->assertSame('2026-12-21', $sewa->tanggal_akhir_sewa->toDateString());
        $this->assertEquals(500, $sewa->kwh_awal);
        $this->assertEquals(1000, $sewa->kapasitas_baterai_kwh);
        // 25 x 100.000 + (1000 - 500) x 2.000
        $this->assertEquals(3500000, $sewa->total_harga);

        $this->assertSame('used', Garasi::find('E001')->status_garasi);
        $this->assertCount(1, Storage::disk('public')->allFiles('bukti-bayar'));
    }

    public function test_halaman_sewa_penyewa_hanya_memuat_garasi_yang_tersedia(): void
    {
        $this->garasi('S001');
        $dipakai = $this->garasi('S002', status: 'used');
        $this->buatSewa($dipakai, User::factory()->create(), '2026-11-20', '2026-11-25');

        $this->get(route('sewa.index'))
            ->assertOk()
            ->assertViewIs('sewa.sewa')
            ->assertViewHas('garasiTersedia', function ($garasi) {
                return $garasi->collapse()->pluck('id')->all() === ['S001'];
            });
    }

    // tes buat lihat list daftar sewa di sisi admin sama perpanjangan sewa

    public function test_admin_hanya_melihat_sewa_yang_aktif_hari_ini(): void
    {
        $penyewa = User::factory()->create(['nama_lengkap' => 'Tes']);
        $aktif   = $this->garasi('S001', status: 'used');
        $selesai = $this->garasi('S002');

        $this->buatSewa($aktif, $penyewa, '2026-11-20', '2026-11-25');
        $this->buatSewa($selesai, $penyewa, '2026-11-01', '2026-11-05');

        $this->masukSebagaiAdmin();

        $this->get(route('sewa.index'))
            ->assertOk()
            ->assertViewIs('sewa.daftar-sewa')
            ->assertSee('S001')
            ->assertSee('Tes')
            ->assertDontSee('S002');
    }

    public function test_admin_dapat_membuka_form_perpanjang_sewa(): void
    {
        $garasi = $this->garasi('S001', status: 'used');
        $sewa   = $this->buatSewa($garasi, $this->penyewa, '2026-11-22', '2026-11-24');

        $this->masukSebagaiAdmin();

        $this->get(route('sewa.extend', ['rentalId' => $sewa->id]))
            ->assertOk()
            ->assertViewIs('sewa.perpanjang-sewa')
            ->assertSee('Perpanjang Sewa Garasi S001');
    }

    public function test_admin_memperpanjang_sewa_bulanan_sampai_25_desember_dengan_biaya_400_ribu(): void
    {
        $garasi = $this->garasi('E001', 'Mobil Listrik', 100000, 2000, 'used');
        $sewa   = $this->buatSewa($garasi, $this->penyewa, '2026-11-22', '2026-12-21', [
            'tipe_sewa'             => 'Bulanan',
            'jenis_kendaraan'       => 'Mobil Listrik',
            'plat_nomor'            => '12345',
            'kwh_awal'              => 500,
            'kapasitas_baterai_kwh' => 1000,
            'total_harga'           => 3500000,
        ]);

        $this->masukSebagaiAdmin();

        $this->kirimPerpanjang($sewa->id, ['tanggal_akhir_baru' => '2026-12-25'])
            ->assertRedirect(route('sewa.index'))
            ->assertSessionHas('status', 'Sewa garasi E001 diperpanjang sampai 25/12/2026. Biaya tambahan: Rp 400.000.')
            ->assertSessionHasNoErrors();

        $perpanjang = PerpanjangSewa::first();
        $this->assertSame($sewa->id, $perpanjang->informasi_sewa_id);
        $this->assertSame('2026-12-21', $perpanjang->tanggal_akhir_lama->toDateString());
        $this->assertSame('2026-12-25', $perpanjang->tanggal_akhir_baru->toDateString());
        // 4 hari x 100.000, tanpa biaya listrik
        $this->assertEquals(400000, $perpanjang->biaya_tambahan);
        $this->assertNotEmpty($perpanjang->bukti_bayar_perpanjang_url);

        $sewa->refresh();
        $this->assertSame('2026-12-25', $sewa->tanggal_akhir_sewa->toDateString());
        // Nilai transaksi awal ndak berubah
        $this->assertEquals(3500000, $sewa->total_harga);
    }
}
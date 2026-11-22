<?php

namespace Tests\Feature;

use App\Models\Garasi;
use App\Models\InformasiSewa;
use App\Models\PerpanjangSewa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Use case "Lihat Laporan Sewa": halaman laporan dengan tab, khususnya Per Tipe Garasi.
 *
 * Data uji (hari ini dibekukan: 22 November 2026):
 *   S001  Budi   22 Nov 2026  Rp  30.000 + perpanjangan Rp 60.000
 *   C001  Siti    5 Nov 2026  Rp 250.000
 *   S002  Budi    3 Des 2026  Rp  30.000
 *   S003  Siti   10 Nov 2025  Rp  30.000
 */
class LaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 11, 22, 10, 0, 0));

        $budi = User::factory()->create(['nama_lengkap' => 'Budi Santoso']);
        $siti = User::factory()->create(['nama_lengkap' => 'Siti Rahma']);

        $a = $this->sewa('S001', 'Sepeda', $budi, '2026-11-22', '2026-11-24', 30000);
        $this->sewa('C001', 'Mobil', $siti, '2026-11-05', '2026-11-10', 250000);
        $this->sewa('S002', 'Sepeda', $budi, '2026-12-03', '2026-12-05', 30000);
        $this->sewa('S003', 'Sepeda', $siti, '2025-11-10', '2025-11-12', 30000);

        PerpanjangSewa::create([
            'informasi_sewa_id'          => $a->id,
            'tanggal_akhir_lama'         => '2026-11-24',
            'tanggal_akhir_baru'         => '2026-11-30',
            'biaya_tambahan'             => 60000,
            'bukti_bayar_perpanjang_url' => 'http://127.0.0.1:8000/storage/bukti-bayar-perpanjangan/contoh.jpg',
        ]);

        $this->actingAs(User::factory()->admin()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function sewa(string $garasiId, string $tipe, User $user, string $mulai, string $akhir, float $total): InformasiSewa
    {
        Garasi::create([
            'id'            => $garasiId,
            'tipe_garasi'   => $tipe,
            'harga_harian'  => 10000,
            'status_garasi' => 'used',
        ]);

        return InformasiSewa::create([
            'user_id'            => $user->id,
            'garasi_id'          => $garasiId,
            'tipe_sewa'          => 'Harian',
            'model_kendaraan'    => 'Heist',
            'merek_kendaraan'    => 'Polygon',
            'jenis_kendaraan'    => $tipe === 'Mobil' ? 'Mobil' : 'Sepeda',
            'plat_nomor'         => $tipe === 'Mobil' ? 'B 1234 XY' : null,
            'tanggal_mulai_sewa' => $mulai,
            'tanggal_akhir_sewa' => $akhir,
            'total_harga'        => $total,
            'bukti_bayar_url'    => 'http://127.0.0.1:8000/storage/bukti-bayar/contoh.jpg',
        ]);
    }

    public function test_tab_tipe_sepeda_memuat_seluruh_transaksi_garasi_sepeda(): void
    {
        $this->get(route('laporan.index', ['tab' => 'tipe', 'tipe_garasi' => 'Sepeda']))
            ->assertOk()
            ->assertViewIs('laporan.laporan')
            ->assertViewHas('daftar', function ($daftar) {
                return $daftar->count() === 3
                    && $daftar->pluck('garasi_id')->sort()->values()->all() === ['S001', 'S002', 'S003'];
            })
            ->assertViewHas('ringkasan', function ($r) {
                return $r['jumlah'] === 3
                    && $r['sewa'] == 90000
                    && $r['perpanjangan'] == 60000
                    && $r['total'] == 150000;
            });
    }

    public function test_tab_tipe_tanpa_transaksi_menampilkan_pesan_kosong(): void
    {
        $this->get(route('laporan.index', ['tab' => 'tipe', 'tipe_garasi' => 'Mobil Listrik']))
            ->assertOk()
            ->assertViewHas('daftar', fn ($daftar) => $daftar->isEmpty())
            ->assertSee('Belum ada transaksi untuk filter ini');
    }

    public function test_keempat_tab_laporan_tampil_di_halaman(): void
    {
        foreach (['bulan', 'hari', 'tipe', 'penyewa'] as $tab) {
            $this->get(route('laporan.index', ['tab' => $tab]))
                ->assertOk()
                ->assertSee('Laporan Sewa Garasi')
                ->assertSee('Per Bulan')
                ->assertSee('Per Hari')
                ->assertSee('Per Tipe Garasi')
                ->assertSee('Per Penyewa');
        }
    }
}
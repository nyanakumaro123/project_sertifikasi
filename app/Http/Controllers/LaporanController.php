<?php

namespace App\Http\Controllers;

use App\Models\InformasiSewa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LaporanController extends Controller
{
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * bagian yang bisa tampilin laporan mau berdasarkan bulan, hari, tipe garasi, atau penyewa.
     */
    public function generateLaporan(Request $request): View
    {
        $tab = $request->query('tab', 'bulan');
        abort_unless(in_array($tab, ['bulan', 'hari', 'tipe', 'penyewa'], true), 404);

        $filterWaktu = $request->query('filter_waktu');
        $tipeGarasi  = $request->query('tipe_garasi');
        $userId      = $request->query('user_id');

        $baris          = collect();   
        $daftar         = collect();   
        $daftarPenyewa  = collect();   
        $sewa           = collect();   

        if ($tab === 'bulan') {
            $request->validate([
                'filter_waktu' => ['nullable', 'regex:/^\d{4}$/'],
            ], [
                'filter_waktu.regex' => 'Tahun harus terdiri dari 4 digit angka.',
            ]);

            $filterWaktu = $filterWaktu ?: (string) today()->year;

            $sewa = InformasiSewa::with('perpanjangSewa')
                ->whereYear('tanggal_mulai_sewa', (int) $filterWaktu)
                ->get();

            $perBulan = $sewa->groupBy(fn ($s) => $s->tanggal_mulai_sewa->month);

            foreach (self::NAMA_BULAN as $nomor => $nama) {
                $baris->push(['label' => $nama] + $this->ringkas($perBulan->get($nomor, collect())));
            }
        }

        if ($tab === 'hari') {
            $request->validate([
                'filter_waktu' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ], [
                'filter_waktu.regex' => 'Bulan tidak valid. Pilih bulan dan tahun yang benar.',
            ]);

            $filterWaktu = $filterWaktu ?: today()->format('Y-m');
            $bulan       = Carbon::createFromFormat('Y-m-d', $filterWaktu . '-01')->startOfDay();

            $sewa = InformasiSewa::with('perpanjangSewa')
                ->whereYear('tanggal_mulai_sewa', $bulan->year)
                ->whereMonth('tanggal_mulai_sewa', $bulan->month)
                ->get();

            $perHari = $sewa->groupBy(fn ($s) => $s->tanggal_mulai_sewa->day);

            for ($hari = 1; $hari <= $bulan->daysInMonth; $hari++) {
                $label = str_pad((string) $hari, 2, '0', STR_PAD_LEFT) . ' ' . self::NAMA_BULAN[$bulan->month];
                $baris->push(['label' => $label] + $this->ringkas($perHari->get($hari, collect())));
            }
        }

        if ($tab === 'tipe') {
            $request->validate([
                'tipe_garasi' => ['nullable', Rule::in(['Sepeda', 'Mobil', 'Mobil Listrik'])],
            ], [
                'tipe_garasi.in' => 'Tipe garasi tidak valid.',
            ]);

            if ($tipeGarasi) {
                $sewa = InformasiSewa::with(['user', 'garasi', 'perpanjangSewa'])
                    ->whereHas('garasi', fn ($q) => $q->where('tipe_garasi', $tipeGarasi))
                    ->orderByDesc('tanggal_mulai_sewa')
                    ->get();
                $daftar = $sewa;
            }
        }

        if ($tab === 'penyewa') {
            $daftarPenyewa = User::where('role', 'penyewa')
                ->where('status_verifikasi', 'approved')
                ->orderBy('nama_lengkap')
                ->get();

            $request->validate([
                'user_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'penyewa')],
            ], [
                'user_id.exists' => 'Penyewa tidak ditemukan.',
            ]);

            if ($userId) {
                $sewa = InformasiSewa::with(['user', 'garasi', 'perpanjangSewa'])
                    ->where('user_id', $userId)
                    ->orderByDesc('tanggal_mulai_sewa')
                    ->get();
                $daftar = $sewa;
            }
        }

        return view('laporan.laporan', [
            'tab'           => $tab,
            'filterWaktu'   => $filterWaktu,
            'tipeGarasi'    => $tipeGarasi,
            'userId'        => $userId,
            'baris'         => $baris,
            'daftar'        => $daftar,
            'daftarPenyewa' => $daftarPenyewa,
            'ringkasan'     => $this->ringkas($sewa),
        ]);
    }

    /**
     * bagian yang bisa menghitung total semua biaya yang harus dibayar sebagai laporan
     */
    private function ringkas(Collection $sewa): array
    {
        $sewaAwal     = (float) $sewa->sum('total_harga');
        $perpanjangan = (float) $sewa->sum(fn ($s) => $s->perpanjangSewa->sum('biaya_tambahan'));

        return [
            'jumlah'       => $sewa->count(),
            'sewa'         => $sewaAwal,
            'perpanjangan' => $perpanjangan,
            'total'        => $sewaAwal + $perpanjangan,
        ];
    }
}
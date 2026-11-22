<?php

namespace App\Http\Controllers;

use App\Models\Garasi;
use App\Models\InformasiSewa;
use App\Models\PerpanjangSewa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SewaController extends Controller
{
    private int $perkalian_harga_bulanan = 25;

    /**
     * bagian buat nampilin daftar garasi untuk penyewa sama admin
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        // bagian yang otomatis bisa kembalikan status dari used jadi available.
        Garasi::where('status_garasi', 'used')
            ->whereDoesntHave('informasiSewa', fn ($q) => $q->whereDate('tanggal_akhir_sewa', '>=', today()))
            ->update(['status_garasi' => 'available']);

        if ($user->role === 'admin') {
            $sewa = InformasiSewa::with(['user', 'garasi'])
                ->whereDate('tanggal_mulai_sewa', '<=', today())
                ->whereDate('tanggal_akhir_sewa', '>=', today())
                ->orderBy('garasi_id')
                ->get();

            return view('sewa.daftar-sewa', compact('sewa'));
        }

        $garasiTersedia = Garasi::where('status_garasi', 'available')
            ->orderBy('id')
            ->get()
            ->groupBy('tipe_garasi');

        return view('sewa.sewa', [
            'garasiTersedia'        => $garasiTersedia,
            'perkalianHargaBulanan' => $this->perkalian_harga_bulanan,
        ]);
    }

    /**
     * bagian yang mengurusi supaya bisa sewa garasi
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Kembalikan garasi yang masa sewanya sudah lewat (sama seperti di index)
        Garasi::where('status_garasi', 'used')
            ->whereDoesntHave('informasiSewa', fn ($q) => $q->whereDate('tanggal_akhir_sewa', '>=', today()))
            ->update(['status_garasi' => 'available']);

        $validated = $request->validate([
            'jenis_kendaraan'       => ['required', Rule::in(['Sepeda', 'Sepeda Motor', 'Mobil', 'Mobil Listrik'])],
            'plat_nomor'            => ['nullable', 'required_unless:jenis_kendaraan,Sepeda', 'string', 'max:15'],
            'merek_kendaraan'       => ['required', 'string', 'max:100'],
            'model_kendaraan'       => ['required', 'string', 'max:100'],
            'tipe_garasi'           => ['required', Rule::in(['Sepeda', 'Mobil', 'Mobil Listrik'])],
            'garasi_id'             => ['required', 'exists:garasi,id'],
            'tipe_sewa'             => ['required', Rule::in(['Harian', 'Bulanan'])],
            'jumlah_hari_sewa'      => ['nullable', 'required_if:tipe_sewa,Harian', 'prohibited_if:tipe_sewa,Bulanan', 'integer', 'min:1', 'max:30'],
            'kapasitas_baterai_kwh' => ['nullable', 'required_if:tipe_garasi,Mobil Listrik', 'numeric', 'gt:0'],
            'kwh_awal'              => ['nullable', 'required_if:tipe_garasi,Mobil Listrik', 'numeric', 'min:0', 'lte:kapasitas_baterai_kwh'],
            'bukti_bayar'           => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'jenis_kendaraan.required'          => 'Jenis kendaraan wajib dipilih.',
            'jenis_kendaraan.in'                => 'Jenis kendaraan tidak valid.',
            'plat_nomor.required_unless'        => 'Nomor plat wajib diisi untuk kendaraan selain sepeda.',
            'plat_nomor.max'                    => 'Nomor plat maksimal 15 karakter.',
            'merek_kendaraan.required'          => 'Merek kendaraan wajib diisi.',
            'model_kendaraan.required'          => 'Model kendaraan wajib diisi.',
            'tipe_garasi.required'              => 'Tipe garasi wajib dipilih.',
            'tipe_garasi.in'                    => 'Tipe garasi tidak valid.',
            'garasi_id.required'                => 'Garasi wajib dipilih.',
            'garasi_id.exists'                  => 'Garasi yang dipilih tidak ditemukan.',
            'tipe_sewa.required'                => 'Tipe sewa wajib dipilih.',
            'tipe_sewa.in'                      => 'Tipe sewa harus Harian atau Bulanan.',
            'jumlah_hari_sewa.required_if'      => 'Jumlah hari wajib diisi untuk sewa harian.',
            'jumlah_hari_sewa.prohibited_if'    => 'Sewa bulanan hanya untuk satu bulan per transaksi. Untuk durasi lebih lama, lakukan transaksi lagi atau perpanjang sewa.',
            'jumlah_hari_sewa.integer'          => 'Jumlah hari harus berupa bilangan bulat.',
            'jumlah_hari_sewa.min'              => 'Jumlah hari minimal 1 dan tidak boleh negatif.',
            'jumlah_hari_sewa.max'              => 'Sewa harian maksimal 30 hari. Untuk durasi lebih lama, pilih sewa bulanan atau lakukan transaksi lagi.',
            'kapasitas_baterai_kwh.required_if' => 'Kapasitas baterai wajib diisi untuk garasi Mobil Listrik.',
            'kapasitas_baterai_kwh.numeric'     => 'Kapasitas baterai harus berupa angka.',
            'kapasitas_baterai_kwh.gt'          => 'Kapasitas baterai harus lebih dari 0 kWh.',
            'kwh_awal.required_if'              => 'kWh awal wajib diisi untuk garasi Mobil Listrik.',
            'kwh_awal.numeric'                  => 'kWh awal harus berupa angka.',
            'kwh_awal.min'                      => 'kWh awal tidak boleh negatif.',
            'kwh_awal.lte'                      => 'kWh awal tidak boleh melebihi kapasitas baterai.',
            'bukti_bayar.required'              => 'Bukti pelunasan wajib diunggah.',
            'bukti_bayar.image'                 => 'Bukti pelunasan harus berupa gambar.',
            'bukti_bayar.mimes'                 => 'Bukti pelunasan harus berformat JPG, JPEG, atau PNG.',
            'bukti_bayar.max'                   => 'Ukuran bukti pelunasan maksimal 2 MB.',
        ]);

        $hariIni    = today();
        $jumlahHari = $validated['tipe_sewa'] === 'Harian' ? (int) $validated['jumlah_hari_sewa'] : null;

        $tanggalAkhir = $validated['tipe_sewa'] === 'Bulanan'
            ? $hariIni->copy()->addMonthNoOverflow()->subDay()
            : $hariIni->copy()->addDays($jumlahHari - 1);

        $sewa = DB::transaction(function () use ($validated, $user, $request, $hariIni, $tanggalAkhir, $jumlahHari) {
            // Kunci baris garasi biar tidak bisa disewa dua orang sekaligus. sehingga hanya satu
            $garasi = Garasi::lockForUpdate()->findOrFail($validated['garasi_id']);

            if ($garasi->tipe_garasi !== $validated['tipe_garasi']) {
                throw ValidationException::withMessages([
                    'garasi_id' => "Garasi {$garasi->id} bukan bertipe {$validated['tipe_garasi']}.",
                ]);
            }

            if (! $this->validasiKecocokan($validated['jenis_kendaraan'], $garasi->tipe_garasi)) {
                throw ValidationException::withMessages([
                    'tipe_garasi' => "Kendaraan {$validated['jenis_kendaraan']} tidak dapat ditempatkan di garasi {$garasi->tipe_garasi}.",
                ]);
            }

            if (! $garasi->isAvailable()) {
                throw ValidationException::withMessages([
                    'garasi_id' => "Garasi {$garasi->id} sedang disewa dan tidak tersedia.",
                ]);
            }

            $garasiListrik = $garasi->tipe_garasi === 'Mobil Listrik';
            $kapasitas     = $garasiListrik ? (float) $validated['kapasitas_baterai_kwh'] : 0.0;
            $kwhAwal       = $garasiListrik ? (float) $validated['kwh_awal'] : 0.0;

            $total = $this->hitungTotalHarga(
                $garasi,
                $validated['tipe_sewa'],
                $jumlahHari ?? 0,
                $kapasitas,
                $kwhAwal
            );

            $path = $request->file('bukti_bayar')->store('bukti-bayar', 'public');

            $sewa = InformasiSewa::create([
                'user_id'               => $user->id,
                'garasi_id'             => $garasi->id,
                'tipe_sewa'             => $validated['tipe_sewa'],
                'model_kendaraan'       => $validated['model_kendaraan'],
                'merek_kendaraan'       => $validated['merek_kendaraan'],
                'jenis_kendaraan'       => $validated['jenis_kendaraan'],
                'plat_nomor'            => $validated['jenis_kendaraan'] === 'Sepeda' ? null : $validated['plat_nomor'],
                'kwh_awal'              => $garasiListrik ? $kwhAwal : null,
                'kapasitas_baterai_kwh' => $garasiListrik ? $kapasitas : null,
                'jumlah_hari_sewa'      => $jumlahHari,
                'tanggal_mulai_sewa'    => $hariIni,
                'tanggal_akhir_sewa'    => $tanggalAkhir,
                'total_harga'           => $total,
                'bukti_bayar_url'       => Storage::disk('public')->url($path),
            ]);

            $garasi->update(['status_garasi' => 'used']);

            return $sewa;
        });

        return redirect()->route('sewa.index')
            ->with('status', "Sewa garasi {$sewa->garasi_id} berhasil sampai "
                . $sewa->tanggal_akhir_sewa->format('d/m/Y')
                . '. Total biaya: Rp ' . number_format($sewa->total_harga, 0, ',', '.') . '.');
    }

    /**
     * bagian yang bisa buat admin perpanjang sewa + nampilin total biaya yang harus dibayar sisanya.
     */
    public function extend(int $rentalId, Request $request): View|RedirectResponse
    {
        $sewa = InformasiSewa::with(['garasi', 'user'])->findOrFail($rentalId);

        if ($sewa->tanggal_akhir_sewa->lt(today())) {
            return redirect()->route('sewa.index')
                ->withErrors(['sewa' => 'Sewa ini sudah berakhir dan tidak dapat diperpanjang.']);
        }

        if ($request->isMethod('get')) {
            return view('sewa.perpanjang-sewa', compact('sewa'));
        }

        $tanggalLama = $sewa->tanggal_akhir_sewa->copy();

        $validated = $request->validate([
            'tanggal_akhir_baru'     => ['required', 'date', 'after:' . $tanggalLama->toDateString()],
            'bukti_bayar_perpanjang' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'tanggal_akhir_baru.required'     => 'Tanggal selesai baru wajib diisi.',
            'tanggal_akhir_baru.date'         => 'Tanggal selesai baru tidak valid.',
            'tanggal_akhir_baru.after'        => 'Tanggal selesai baru harus setelah tanggal selesai saat ini ('
                                                  . $tanggalLama->format('d/m/Y') . ').',
            'bukti_bayar_perpanjang.required' => 'Bukti pelunasan wajib diunggah.',
            'bukti_bayar_perpanjang.image'    => 'Bukti pelunasan harus berupa gambar.',
            'bukti_bayar_perpanjang.mimes'    => 'Bukti pelunasan harus berformat JPG, JPEG, atau PNG.',
            'bukti_bayar_perpanjang.max'      => 'Ukuran bukti pelunasan maksimal 2 MB.',
        ]);

        $tanggalBaru = Carbon::parse($validated['tanggal_akhir_baru'])->startOfDay();
        $selisihHari = (int) round($tanggalLama->diffInDays($tanggalBaru));

        // Bagian perpanjangan dihitung per hari, tapi belum ada bagian perkalian isi listrik waktu perpanjangan.
        $biaya = $this->hitungTotalHarga($sewa->garasi, 'Harian', $selisihHari, 0.0, 0.0);

        DB::transaction(function () use ($request, $sewa, $tanggalLama, $tanggalBaru, $biaya) {
            $path = $request->file('bukti_bayar_perpanjang')->store('bukti-bayar-perpanjangan', 'public');

            PerpanjangSewa::create([
                'informasi_sewa_id'          => $sewa->id,
                'tanggal_akhir_lama'         => $tanggalLama,
                'tanggal_akhir_baru'         => $tanggalBaru,
                'biaya_tambahan'             => $biaya,
                'bukti_bayar_perpanjang_url' => Storage::disk('public')->url($path),
            ]);

            $sewa->update(['tanggal_akhir_sewa' => $tanggalBaru]);
        });

        return redirect()->route('sewa.index')
            ->with('status', "Sewa garasi {$sewa->garasi_id} diperpanjang sampai "
                . $tanggalBaru->format('d/m/Y')
                . '. Biaya tambahan: Rp ' . number_format($biaya, 0, ',', '.') . '.');
    }

    /**
     * bagian yang bisa validasi antara tipe kendaraan sama tipe garasinya
     */
    public function validasiKecocokan(string $jenisKendaraan, string $tipeGarasi): bool
    {
        $aturan = [
            'Sepeda'        => ['Sepeda'],
            'Sepeda Motor'  => ['Sepeda'],
            'Mobil'         => ['Mobil'],
            'Mobil Listrik' => ['Mobil', 'Mobil Listrik'],
        ];

        return in_array($tipeGarasi, $aturan[$jenisKendaraan] ?? [], true);
    }

    /**
     * bagian yang bisa hitung total harga sewa garasi
     * Harian  : harga_harian x jumlah_hari.
     * Bulanan : harga_harian x perkalian_harga_bulanan (25).
     * Garasi Mobil Listrik ditambah biaya isi listrik hingga penuh.
     */
    public function hitungTotalHarga(
        Garasi $garasi,
        string $tipeSewa,
        int $jumlahHari,
        float $kapasitasBateraiKwh,
        float $kwhAwal
    ): float {
        $total = $tipeSewa === 'Bulanan'
            ? $garasi->harga_harian * $this->perkalian_harga_bulanan
            : $garasi->harga_harian * $jumlahHari;

        if ($garasi->tipe_garasi === 'Mobil Listrik') {
            $kwhDiisi = max($kapasitasBateraiKwh - $kwhAwal, 0);
            $total   += $kwhDiisi * (float) ($garasi->harga_isi_listrik_per_kwh ?? 0);
        }

        return round((float) $total, 2);
    }
}
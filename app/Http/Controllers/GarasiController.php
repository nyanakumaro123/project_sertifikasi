<?php

namespace App\Http\Controllers;

use App\Models\Garasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GarasiController extends Controller
{
    private const MAKSIMAL_PER_TIPE = 999;

    private const PREFIX = [
        'Sepeda'        => 'S',
        'Mobil'         => 'C',
        'Mobil Listrik' => 'E',
    ];

    /**
     * bagian yang tampilin daftar garasi di sisi admin
     */
    public function index(): View
    {
        $garasi = Garasi::orderBy('id')->paginate(25);

        return view('garasi.garasiList', compact('garasi'));
    }

    /**
     * bagian buat admin bisa bikin garasi berkelompok sekaligus.
     */
    public function storeBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tipe_garasi'               => ['required', Rule::in(array_keys(self::PREFIX))],
            'jumlah'                    => ['required', 'integer', 'min:1', 'max:' . self::MAKSIMAL_PER_TIPE],
            'harga_harian'              => ['required', 'numeric', 'min:0'],
            'harga_isi_listrik_per_kwh' => ['nullable', 'required_if:tipe_garasi,Mobil Listrik', 'numeric', 'min:0'],
        ], [
            'tipe_garasi.required'                    => 'Tipe garasi wajib dipilih.',
            'tipe_garasi.in'                          => 'Tipe garasi tidak valid.',
            'jumlah.required'                         => 'Jumlah garasi wajib diisi.',
            'jumlah.integer'                          => 'Jumlah garasi harus berupa bilangan bulat.',
            'jumlah.min'                              => 'Jumlah garasi minimal 1 dan tidak boleh negatif.',
            'jumlah.max'                              => 'Jumlah garasi maksimal ' . self::MAKSIMAL_PER_TIPE . ' unit.',
            'harga_harian.required'                   => 'Harga sewa harian wajib diisi.',
            'harga_harian.numeric'                    => 'Harga sewa harian harus berupa angka.',
            'harga_harian.min'                        => 'Harga sewa harian tidak boleh negatif.',
            'harga_isi_listrik_per_kwh.required_if'   => 'Harga isi listrik per kWh wajib diisi untuk Mobil Listrik.',
            'harga_isi_listrik_per_kwh.numeric'       => 'Harga isi listrik per kWh harus berupa angka.',
            'harga_isi_listrik_per_kwh.min'           => 'Harga isi listrik per kWh tidak boleh negatif.',
        ]);

        $tipe   = $validated['tipe_garasi'];
        $prefix = self::PREFIX[$tipe];
        $jumlah = (int) $validated['jumlah'];

        [$idAwal, $idAkhir] = DB::transaction(function () use ($tipe, $prefix, $jumlah, $validated) {
            // Kode terakhir untuk tipe ini, misalnya "C009" -> 9 (null jika belum ada)
            $kodeTerakhir  = Garasi::where('id', 'like', $prefix . '%')->max('id');
            $nomorTerakhir = $kodeTerakhir ? (int) substr($kodeTerakhir, 1) : 0;
            $sisaKuota     = self::MAKSIMAL_PER_TIPE - $nomorTerakhir;

            if ($jumlah > $sisaKuota) {
                throw ValidationException::withMessages([
                    'jumlah' => $sisaKuota === 0
                        ? "Garasi {$tipe} sudah mencapai batas maksimal " . self::MAKSIMAL_PER_TIPE . ' unit, tidak dapat menambah garasi lagi.'
                        : "Jumlah garasi {$tipe} melebihi batas maksimal " . self::MAKSIMAL_PER_TIPE
                          . " unit. Saat ini sudah ada {$nomorTerakhir} unit, sisa kuota {$sisaKuota} unit.",
                ]);
            }

            $hargaListrik = $tipe === 'Mobil Listrik' ? $validated['harga_isi_listrik_per_kwh'] : null;
            $sekarang     = now();
            $baris        = [];

            for ($i = 1; $i <= $jumlah; $i++) {
                $baris[] = [
                    'id'                        => $prefix . str_pad($nomorTerakhir + $i, 3, '0', STR_PAD_LEFT),
                    'tipe_garasi'               => $tipe,
                    'harga_harian'              => $validated['harga_harian'],
                    'harga_isi_listrik_per_kwh' => $hargaListrik,
                    'status_garasi'             => 'available',
                    'created_at'                => $sekarang,
                    'updated_at'                => $sekarang,
                ];
            }

            Garasi::insert($baris);

            return [$baris[0]['id'], $baris[$jumlah - 1]['id']];
        });

        return redirect()->route('garasi.index')
            ->with('status', "{$jumlah} garasi {$tipe} berhasil dibuat (ID {$idAwal} sampai {$idAkhir}).");
    }
}
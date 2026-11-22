@php
    $dataGarasi = $garasiTersedia->collapse()->map(fn ($g) => [
        'id'      => $g->id,
        'tipe'    => $g->tipe_garasi,
        'harga'   => (float) $g->harga_harian,
        'listrik' => (float) ($g->harga_isi_listrik_per_kwh ?? 0),
        'label'   => $g->id . ' - Rp ' . number_format($g->harga_harian, 0, ',', '.') . ' per hari',
    ])->values();

    $kelasSelect = 'block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-800">Sewa Garasi</h1>
    </x-slot>

    <p class="text-sm text-gray-600 mb-4">
        Garasi tersedia hari ini:
        Sepeda {{ $garasiTersedia->get('Sepeda', collect())->count() }} unit,
        Mobil {{ $garasiTersedia->get('Mobil', collect())->count() }} unit,
        Mobil Listrik {{ $garasiTersedia->get('Mobil Listrik', collect())->count() }} unit.
    </p>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl">
        <form method="POST" action="{{ route('sewa.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- bagian detail kendaraan --}}
            <h2 class="font-semibold text-gray-800 mb-3">Detail Kendaraan</h2>

            <div>
                <x-input-label for="jenis_kendaraan" value="Jenis Kendaraan" />
                <select id="jenis_kendaraan" name="jenis_kendaraan" required class="{{ $kelasSelect }}">
                    <option value="">-- Pilih jenis kendaraan --</option>
                    @foreach (['Sepeda', 'Sepeda Motor', 'Mobil', 'Mobil Listrik'] as $jenis)
                        <option value="{{ $jenis }}" @selected(old('jenis_kendaraan') === $jenis)>{{ $jenis }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('jenis_kendaraan')" class="mt-2" />
            </div>

            <div id="bagian-plat" class="mt-4">
                <x-input-label for="plat_nomor" value="Nomor Plat Kendaraan" />
                <x-text-input id="plat_nomor" class="block mt-1 w-full" type="text" name="plat_nomor"
                              :value="old('plat_nomor')" maxlength="15" />
                <x-input-error :messages="$errors->get('plat_nomor')" class="mt-2" />
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="merek_kendaraan" value="Merek" />
                    <x-text-input id="merek_kendaraan" class="block mt-1 w-full" type="text" name="merek_kendaraan"
                                  :value="old('merek_kendaraan')" required />
                    <x-input-error :messages="$errors->get('merek_kendaraan')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="model_kendaraan" value="Tipe / Model" />
                    <x-text-input id="model_kendaraan" class="block mt-1 w-full" type="text" name="model_kendaraan"
                                  :value="old('model_kendaraan')" required />
                    <x-input-error :messages="$errors->get('model_kendaraan')" class="mt-2" />
                </div>
            </div>

            {{-- bagian tipe sewa (harian/bulanan)--}}
            <h2 class="font-semibold text-gray-800 mt-8 mb-3">Masa Sewa</h2>

            <div>
                <x-input-label for="tipe_sewa" value="Tipe Sewa" />
                <select id="tipe_sewa" name="tipe_sewa" required class="{{ $kelasSelect }}">
                    <option value="">-- Pilih tipe sewa --</option>
                    <option value="Harian" @selected(old('tipe_sewa') === 'Harian')>Harian</option>
                    <option value="Bulanan" @selected(old('tipe_sewa') === 'Bulanan')>Bulanan</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Harian: maksimal 30 hari. Bulanan: satu bulan penuh per transaksi
                    (biaya {{ $perkalianHargaBulanan }} x harga harian). Untuk durasi lebih lama,
                    lakukan transaksi lagi atau hubungi Admin untuk perpanjangan.
                </p>
                <x-input-error :messages="$errors->get('tipe_sewa')" class="mt-2" />
            </div>

            <div id="bagian-hari" class="mt-4 hidden">
                <x-input-label for="jumlah_hari_sewa" value="Jumlah Hari (maksimal 30)" />
                <x-text-input id="jumlah_hari_sewa" class="block mt-1 w-full" type="number" name="jumlah_hari_sewa"
                              :value="old('jumlah_hari_sewa')" min="1" max="30" step="1" />
                <x-input-error :messages="$errors->get('jumlah_hari_sewa')" class="mt-2" />
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="tanggal_mulai_tampil" value="Tanggal Mulai Sewa (hari ini)" />
                    <x-text-input id="tanggal_mulai_tampil" class="block mt-1 w-full bg-gray-100" type="text"
                                  :value="today()->format('d/m/Y')" readonly />
                </div>
                <div>
                    <x-input-label for="tanggal_selesai_tampil" value="Tanggal Selesai Sewa (otomatis)" />
                    <x-text-input id="tanggal_selesai_tampil" class="block mt-1 w-full bg-gray-100" type="text" readonly />
                </div>
            </div>

            {{-- bagian garasi --}}
            <h2 class="font-semibold text-gray-800 mt-8 mb-3">Garasi</h2>

            <div>
                <x-input-label for="tipe_garasi" value="Tipe Garasi" />
                <select id="tipe_garasi" name="tipe_garasi" required class="{{ $kelasSelect }}">
                    <option value="">-- Pilih jenis kendaraan dahulu --</option>
                </select>
                <x-input-error :messages="$errors->get('tipe_garasi')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="garasi_id" value="Garasi yang Tersedia" />
                <select id="garasi_id" name="garasi_id" required class="{{ $kelasSelect }}">
                    <option value="">-- Pilih tipe garasi dahulu --</option>
                </select>
                <x-input-error :messages="$errors->get('garasi_id')" class="mt-2" />
            </div>

            {{-- bagian yang cuman tampil kalau tipe garasinya Mobil Listrik--}}
            <div id="bagian-kwh" class="mt-4 hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="kapasitas_baterai_kwh" value="Kapasitas Baterai (kWh)" />
                    <x-text-input id="kapasitas_baterai_kwh" class="block mt-1 w-full" type="number"
                                  name="kapasitas_baterai_kwh" :value="old('kapasitas_baterai_kwh')"
                                  min="0.1" step="0.1" />
                    <x-input-error :messages="$errors->get('kapasitas_baterai_kwh')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="kwh_awal" value="kWh Awal (saat dititipkan)" />
                    <x-text-input id="kwh_awal" class="block mt-1 w-full" type="number"
                                  name="kwh_awal" :value="old('kwh_awal')" min="0" step="0.1" />
                    <x-input-error :messages="$errors->get('kwh_awal')" class="mt-2" />
                </div>
            </div>

            {{-- bagian total sama butki pelunasan --}}
            <div class="mt-8 rounded-md bg-gray-50 border border-gray-200 p-4">
                <p class="text-sm text-gray-700">
                    Estimasi total biaya sewa:
                    <span id="total-estimasi" class="font-semibold">-</span>
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    Garasi Mobil Listrik sudah termasuk biaya isi listrik hingga penuh.
                    Total akhir dihitung ulang oleh sistem saat data disimpan.
                </p>
            </div>

            <div class="mt-4">
                <x-input-label for="bukti_bayar" value="Bukti Pelunasan Biaya (gambar JPG/PNG, maksimal 2 MB)" />
                <input id="bukti_bayar" name="bukti_bayar" type="file" accept="image/png,image/jpeg" required
                       class="block mt-1 w-full text-sm text-gray-700 border border-gray-300 rounded-md cursor-pointer">
                <x-input-error :messages="$errors->get('bukti_bayar')" class="mt-2" />
            </div>

            <div class="mt-6">
                <x-primary-button>Sewa Garasi</x-primary-button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var KALI_BULANAN = {{ $perkalianHargaBulanan }};
            var HARI_INI = '{{ today()->toDateString() }}';
            var semuaGarasi = @json($dataGarasi);

            // Nilai lama (setelah gagal validasi), dipake sekali waktu halaman dibuka
            var awal = { tipe: @json(old('tipe_garasi')), garasi: @json(old('garasi_id')) };

            var aturan = {
                'Sepeda': ['Sepeda'],
                'Sepeda Motor': ['Sepeda'],
                'Mobil': ['Mobil'],
                'Mobil Listrik': ['Mobil', 'Mobil Listrik']
            };

            var $ = function (id) { return document.getElementById(id); };
            var jenis = $('jenis_kendaraan'), plat = $('plat_nomor'), bagianPlat = $('bagian-plat');
            var tipeSewa = $('tipe_sewa'), hari = $('jumlah_hari_sewa'), bagianHari = $('bagian-hari');
            var tanggalSelesai = $('tanggal_selesai_tampil');
            var tipeGarasi = $('tipe_garasi'), garasi = $('garasi_id');
            var bagianKwh = $('bagian-kwh'), kapasitas = $('kapasitas_baterai_kwh'), kwhAwal = $('kwh_awal');
            var total = $('total-estimasi');

            function pad(n) { return n < 10 ? '0' + n : '' + n; }
            function formatTanggal(d) { return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear(); }

            function tanggalAkhir() {
                var p = HARI_INI.split('-');
                var mulai = new Date(+p[0], +p[1] - 1, +p[2]);

                if (tipeSewa.value === 'Harian') {
                    var n = parseInt(hari.value, 10);
                    if (!(n >= 1)) { return null; }
                    return new Date(mulai.getFullYear(), mulai.getMonth(), mulai.getDate() + n - 1);
                }
                if (tipeSewa.value === 'Bulanan') {
                    var bulanDepan = mulai.getMonth() + 1;
                    var hariTerakhir = new Date(mulai.getFullYear(), bulanDepan + 1, 0).getDate();
                    var tgl = Math.min(mulai.getDate(), hariTerakhir);
                    return new Date(mulai.getFullYear(), bulanDepan, tgl - 1);
                }
                return null;
            }

            function hitungTotal() {
                var g = semuaGarasi.find(function (x) { return x.id === garasi.value; });
                if (!g || !tipeSewa.value) { return '-'; }

                var jumlah;
                if (tipeSewa.value === 'Bulanan') {
                    jumlah = g.harga * KALI_BULANAN;
                } else {
                    var n = parseInt(hari.value, 10);
                    if (!(n >= 1)) { return '-'; }
                    jumlah = g.harga * n;
                }

                if (g.tipe === 'Mobil Listrik') {
                    var kap = parseFloat(kapasitas.value), awalKwh = parseFloat(kwhAwal.value);
                    if (isNaN(kap) || isNaN(awalKwh)) { return '-'; }
                    jumlah += Math.max(kap - awalKwh, 0) * g.listrik;
                }

                return 'Rp ' + jumlah.toLocaleString('id-ID', { maximumFractionDigits: 2 });
            }

            function perbarui() {
                var akhir = tanggalAkhir();
                tanggalSelesai.value = akhir ? formatTanggal(akhir) : '';
                total.textContent = hitungTotal();
            }

            function aturPlat() {
                var perluPlat = jenis.value !== 'Sepeda';
                bagianPlat.classList.toggle('hidden', !perluPlat);
                plat.disabled = !perluPlat;
                plat.required = perluPlat;
                if (!perluPlat) { plat.value = ''; }
            }

            function aturSewa() {
                var harian = tipeSewa.value === 'Harian';
                bagianHari.classList.toggle('hidden', !harian);
                hari.disabled = !harian;
                hari.required = harian;
                if (!harian) { hari.value = ''; }
                perbarui();
            }

            function aturKwh() {
                var listrik = tipeGarasi.value === 'Mobil Listrik';
                bagianKwh.classList.toggle('hidden', !listrik);
                [kapasitas, kwhAwal].forEach(function (i) {
                    i.disabled = !listrik;
                    i.required = listrik;
                    if (!listrik) { i.value = ''; }
                });
            }

            function aturGarasi() {
                var tipe = tipeGarasi.value;
                var dipilih = garasi.value || awal.garasi;
                awal.garasi = null;

                var cocok = semuaGarasi.filter(function (g) { return g.tipe === tipe; });
                var teks = !tipe ? '-- Pilih tipe garasi dahulu --'
                    : (cocok.length ? '-- Pilih garasi --' : 'Tidak ada garasi tersedia untuk tipe ini');

                garasi.innerHTML = '';
                garasi.appendChild(new Option(teks, ''));
                cocok.forEach(function (g) {
                    garasi.appendChild(new Option(g.label, g.id, false, g.id === dipilih));
                });

                aturKwh();
                perbarui();
            }

            function aturTipeGarasi() {
                var daftar = aturan[jenis.value] || [];
                var dipilih = tipeGarasi.value || awal.tipe;
                awal.tipe = null;

                tipeGarasi.innerHTML = '';
                tipeGarasi.appendChild(new Option(
                    daftar.length ? '-- Pilih tipe garasi --' : '-- Pilih jenis kendaraan dahulu --', ''));
                daftar.forEach(function (t) {
                    tipeGarasi.appendChild(new Option(t, t, false, t === dipilih));
                });

                aturGarasi();
            }

            jenis.addEventListener('change', function () { aturPlat(); aturTipeGarasi(); });
            tipeSewa.addEventListener('change', aturSewa);
            tipeGarasi.addEventListener('change', aturGarasi);
            garasi.addEventListener('change', perbarui);
            [hari, kapasitas, kwhAwal].forEach(function (i) { i.addEventListener('input', perbarui); });

            aturPlat();
            aturSewa();
            aturTipeGarasi();
        });
    </script>
</x-app-layout>
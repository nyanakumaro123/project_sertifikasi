@php
    $tanggalMinimal = $sewa->tanggal_akhir_sewa->copy()->addDay()->toDateString();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-800">Perpanjang Sewa Garasi {{ $sewa->garasi_id }}</h1>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-xl">
        {{-- bagian informasi sewa saat ini --}}
        <h2 class="font-semibold text-gray-800 mb-3">Sewa Saat Ini</h2>
        <dl class="grid grid-cols-3 gap-y-2 text-sm">
            <dt class="text-gray-500">Garasi</dt>
            <dd class="col-span-2">{{ $sewa->garasi_id }} ({{ $sewa->garasi->tipe_garasi }})</dd>

            <dt class="text-gray-500">Penyewa</dt>
            <dd class="col-span-2">{{ $sewa->user->nama_lengkap }}</dd>

            <dt class="text-gray-500">Kendaraan</dt>
            <dd class="col-span-2">
                {{ $sewa->jenis_kendaraan }} - {{ $sewa->merek_kendaraan }} {{ $sewa->model_kendaraan }}
                @if ($sewa->plat_nomor) ({{ $sewa->plat_nomor }}) @endif
            </dd>

            <dt class="text-gray-500">Tipe Sewa</dt>
            <dd class="col-span-2">{{ $sewa->tipe_sewa }}</dd>

            <dt class="text-gray-500">Tanggal Mulai</dt>
            <dd class="col-span-2">{{ $sewa->tanggal_mulai_sewa->format('d/m/Y') }}</dd>

            <dt class="text-gray-500">Tanggal Selesai</dt>
            <dd class="col-span-2">{{ $sewa->tanggal_akhir_sewa->format('d/m/Y') }}</dd>

            <dt class="text-gray-500">Harga Harian</dt>
            <dd class="col-span-2">Rp {{ number_format($sewa->garasi->harga_harian, 0, ',', '.') }}</dd>
        </dl>

        <hr class="my-6">

        {{-- bagian form perpanjangan sewa --}}
        <h2 class="font-semibold text-gray-800 mb-3">Perpanjangan</h2>

        <form method="POST" action="{{ route('sewa.extend.store', ['rentalId' => $sewa->id]) }}"
              enctype="multipart/form-data">
            @csrf

            <div>
                <x-input-label for="tanggal_akhir_baru" value="Tanggal Selesai Baru" />
                <x-text-input id="tanggal_akhir_baru" class="block mt-1 w-full" type="date"
                              name="tanggal_akhir_baru" :value="old('tanggal_akhir_baru')"
                              min="{{ $tanggalMinimal }}" required />
                <p class="mt-1 text-xs text-gray-500">
                    Harus setelah {{ $sewa->tanggal_akhir_sewa->format('d/m/Y') }}. Tidak ada batas durasi perpanjangan.
                </p>
                <x-input-error :messages="$errors->get('tanggal_akhir_baru')" class="mt-2" />
            </div>

            <div class="mt-4 rounded-md bg-gray-50 border border-gray-200 p-4">
                <p class="text-sm text-gray-700">
                    Tambahan: <span id="tambahan-hari" class="font-semibold">-</span>
                </p>
                <p class="mt-1 text-sm text-gray-700">
                    Estimasi sisa biaya yang harus dibayarkan:
                    <span id="estimasi-biaya" class="font-semibold">-</span>
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    Dihitung per hari tambahan x harga harian, tanpa biaya isi listrik.
                    Biaya akhir dihitung ulang oleh sistem saat data disimpan.
                </p>
            </div>

            <div class="mt-4">
                <x-input-label for="bukti_bayar_perpanjang" value="Bukti Pelunasan Biaya (gambar JPG/PNG, maksimal 2 MB)" />
                <input id="bukti_bayar_perpanjang" name="bukti_bayar_perpanjang" type="file"
                       accept="image/png,image/jpeg" required
                       class="block mt-1 w-full text-sm text-gray-700 border border-gray-300 rounded-md cursor-pointer">
                <x-input-error :messages="$errors->get('bukti_bayar_perpanjang')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Perpanjang Sewa</x-primary-button>
                <a href="{{ route('sewa.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">Batal</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var HARGA_HARIAN = {{ (float) $sewa->garasi->harga_harian }};
            var AKHIR_LAMA = '{{ $sewa->tanggal_akhir_sewa->toDateString() }}';

            var input = document.getElementById('tanggal_akhir_baru');
            var hari = document.getElementById('tambahan-hari');
            var biaya = document.getElementById('estimasi-biaya');

            function utc(teks) {
                var p = teks.split('-');
                return Date.UTC(+p[0], +p[1] - 1, +p[2]);
            }

            function perbarui() {
                if (!input.value) {
                    hari.textContent = '-';
                    biaya.textContent = '-';
                    return;
                }

                var selisih = Math.round((utc(input.value) - utc(AKHIR_LAMA)) / 86400000);

                if (selisih < 1) {
                    hari.textContent = 'tanggal harus setelah tanggal selesai saat ini';
                    biaya.textContent = '-';
                    return;
                }

                hari.textContent = selisih + ' hari';
                biaya.textContent = 'Rp ' + (selisih * HARGA_HARIAN).toLocaleString('id-ID', { maximumFractionDigits: 2 });
            }

            input.addEventListener('input', perbarui);
            perbarui();
        });
    </script>
</x-app-layout>
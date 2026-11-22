<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-800">Buat Garasi Berkelompok</h1>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-xl">
        <form method="POST" action="{{ route('garasi.store-batch') }}">
            @csrf

            <div>
                <x-input-label for="tipe_garasi" value="Tipe Garasi" />
                <select id="tipe_garasi" name="tipe_garasi" required
                        class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">-- Pilih tipe garasi --</option>
                    <option value="Sepeda" @selected(old('tipe_garasi') === 'Sepeda')>Sepeda (termasuk sepeda motor)</option>
                    <option value="Mobil" @selected(old('tipe_garasi') === 'Mobil')>Mobil</option>
                    <option value="Mobil Listrik" @selected(old('tipe_garasi') === 'Mobil Listrik')>Mobil Listrik</option>
                </select>
                <x-input-error :messages="$errors->get('tipe_garasi')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="jumlah" value="Jumlah Garasi (maksimum 999 per tipe)" />
                <x-text-input id="jumlah" class="block mt-1 w-full" type="number" name="jumlah"
                              :value="old('jumlah')" min="1" max="999" step="1" required />
                <x-input-error :messages="$errors->get('jumlah')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="harga_harian" value="Harga Sewa Harian (Rp)" />
                <x-text-input id="harga_harian" class="block mt-1 w-full" type="number" name="harga_harian"
                              :value="old('harga_harian')" min="0" step="0.01" required />
                <x-input-error :messages="$errors->get('harga_harian')" class="mt-2" />
            </div>

            {{-- bagian yang cuman tampil waktu tipe garasinya mobil listrik --}}
            <div id="bagian-kwh" class="mt-4 {{ old('tipe_garasi') === 'Mobil Listrik' ? '' : 'hidden' }}">
                <x-input-label for="harga_isi_listrik_per_kwh" value="Harga Isi Listrik per kWh (Rp)" />
                <x-text-input id="harga_isi_listrik_per_kwh" class="block mt-1 w-full" type="number"
                              name="harga_isi_listrik_per_kwh" :value="old('harga_isi_listrik_per_kwh')"
                              min="0" step="0.01"
                              :disabled="old('tipe_garasi') !== 'Mobil Listrik'" />
                <x-input-error :messages="$errors->get('harga_isi_listrik_per_kwh')" class="mt-2" />
            </div>

            <p class="mt-4 text-sm text-gray-500">
                ID garasi dibuat otomatis: S untuk Sepeda, C untuk Mobil, E untuk Mobil Listrik,
                diikuti tiga digit urut (contoh S001, C010, E005).
            </p>

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Buat Garasi</x-primary-button>
                <a href="{{ route('garasi.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">Batal</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tipe   = document.getElementById('tipe_garasi');
            var bagian = document.getElementById('bagian-kwh');
            var input  = document.getElementById('harga_isi_listrik_per_kwh');

            function atur() {
                var listrik = tipe.value === 'Mobil Listrik';
                bagian.classList.toggle('hidden', !listrik);
                input.disabled = !listrik;
                input.required = listrik;
                if (!listrik) { input.value = ''; }
            }

            tipe.addEventListener('change', atur);
            atur();
        });
    </script>
</x-app-layout>
@php
    $tabs = [
        'bulan'   => 'Per Bulan',
        'hari'    => 'Per Hari',
        'tipe'    => 'Per Tipe Garasi',
        'penyewa' => 'Per Penyewa',
    ];
    $kelasInput = 'border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm';
    $rupiah = fn ($angka) => 'Rp ' . number_format($angka, 0, ',', '.');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-800">Laporan Sewa Garasi</h1>
    </x-slot>

    {{-- bagian macam-macam tab --}}
    <div class="border-b border-gray-300 mb-4">
        <nav class="flex flex-wrap gap-x-6">
            @foreach ($tabs as $kode => $nama)
                <a href="{{ route('laporan.index', ['tab' => $kode]) }}"
                   class="pb-2 text-sm font-medium border-b-2 {{ $tab === $kode
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $nama }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- bagian filter yang dijalankan ketika button "tampilkan" ditekan per tabnya --}}
    <div class="bg-white shadow rounded-lg p-4 mb-4">
        <form method="GET" action="{{ route('laporan.index') }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">

            @if ($tab === 'bulan')
                <div>
                    <x-input-label for="filter_waktu" value="Tahun" />
                    <input id="filter_waktu" name="filter_waktu" type="number" min="2000" max="2100" step="1"
                           value="{{ $filterWaktu }}" class="block mt-1 w-32 {{ $kelasInput }}">
                </div>
            @elseif ($tab === 'hari')
                <div>
                    <x-input-label for="filter_waktu" value="Bulan" />
                    <input id="filter_waktu" name="filter_waktu" type="month"
                           value="{{ $filterWaktu }}" class="block mt-1 {{ $kelasInput }}">
                </div>
            @elseif ($tab === 'tipe')
                <div>
                    <x-input-label for="tipe_garasi" value="Tipe Garasi" />
                    <select id="tipe_garasi" name="tipe_garasi" class="block mt-1 {{ $kelasInput }}">
                        <option value="">-- Pilih tipe garasi --</option>
                        @foreach (['Sepeda', 'Mobil', 'Mobil Listrik'] as $t)
                            <option value="{{ $t }}" @selected($tipeGarasi === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div>
                    <x-input-label for="user_id" value="Penyewa" />
                    <select id="user_id" name="user_id" class="block mt-1 {{ $kelasInput }}">
                        <option value="">-- Pilih penyewa --</option>
                        @foreach ($daftarPenyewa as $p)
                            <option value="{{ $p->id }}" @selected((string) $userId === (string) $p->id)>
                                {{ $p->nama_lengkap }} ({{ $p->nik }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <x-primary-button>Tampilkan</x-primary-button>
        </form>

        @foreach (['filter_waktu', 'tipe_garasi', 'user_id'] as $kolom)
            <x-input-error :messages="$errors->get($kolom)" class="mt-2" />
        @endforeach
    </div>

    {{-- bagian yang menampilkan tabel rekap berdasarkan filter "per bulan" dan "per hari" --}}
    @if (in_array($tab, ['bulan', 'hari'], true))
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">{{ $tab === 'bulan' ? 'Bulan' : 'Tanggal' }}</th>
                        <th class="px-4 py-3 text-right">Jumlah Transaksi</th>
                        <th class="px-4 py-3 text-right">Sewa Awal</th>
                        <th class="px-4 py-3 text-right">Perpanjangan</th>
                        <th class="px-4 py-3 text-right">Pendapatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($baris as $b)
                        <tr>
                            <td class="px-4 py-2">{{ $b['label'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $b['jumlah'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $rupiah($b['sewa']) }}</td>
                            <td class="px-4 py-2 text-right">{{ $rupiah($b['perpanjangan']) }}</td>
                            <td class="px-4 py-2 text-right font-medium">{{ $rupiah($b['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td class="px-4 py-3">Total {{ $tab === 'bulan' ? 'Tahun ' . $filterWaktu : 'Bulan ' . $filterWaktu }}</td>
                        <td class="px-4 py-3 text-right">{{ $ringkasan['jumlah'] }}</td>
                        <td class="px-4 py-3 text-right">{{ $rupiah($ringkasan['sewa']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $rupiah($ringkasan['perpanjangan']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $rupiah($ringkasan['total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="mt-2 text-xs text-gray-500">
            Pendapatan dikelompokkan menurut tanggal mulai sewa. Biaya perpanjangan masuk ke periode sewa asalnya.
        </p>

    {{-- bagian yang menampilkan daftar transaksi berdasarkan filter "per tipe garasi" dan "Per pernyewa" --}}
    @else
        @php
            $sudahFilter = $tab === 'tipe' ? filled($tipeGarasi) : filled($userId);
        @endphp

        @if (! $sudahFilter)
            <div class="bg-white shadow rounded-lg p-6 text-sm text-gray-500">
                {{ $tab === 'tipe' ? 'Pilih tipe garasi' : 'Pilih penyewa' }} lalu tekan Tampilkan.
            </div>
        @else
            <div class="bg-white shadow rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Garasi</th>
                            <th class="px-4 py-3">Penyewa</th>
                            <th class="px-4 py-3">Kendaraan</th>
                            <th class="px-4 py-3">Tipe Sewa</th>
                            <th class="px-4 py-3">Mulai</th>
                            <th class="px-4 py-3">Selesai</th>
                            <th class="px-4 py-3 text-right">Total Harga</th>
                            <th class="px-4 py-3 text-right">Perpanjangan</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($daftar as $s)
                            @php $biayaPerpanjang = (float) $s->perpanjangSewa->sum('biaya_tambahan'); @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <span class="font-medium">{{ $s->garasi_id }}</span><br>
                                    <span class="text-xs text-gray-500">{{ $s->garasi->tipe_garasi }}</span>
                                </td>
                                <td class="px-4 py-2">{{ $s->user->nama_lengkap }}</td>
                                <td class="px-4 py-2">
                                    {{ $s->jenis_kendaraan }}<br>
                                    <span class="text-xs text-gray-500">{{ $s->merek_kendaraan }} {{ $s->model_kendaraan }}</span>
                                </td>
                                <td class="px-4 py-2">{{ $s->tipe_sewa }}</td>
                                <td class="px-4 py-2">{{ $s->tanggal_mulai_sewa->format('d/m/Y') }}</td>
                                <td class="px-4 py-2">{{ $s->tanggal_akhir_sewa->format('d/m/Y') }}</td>
                                <td class="px-4 py-2 text-right">{{ $rupiah($s->total_harga) }}</td>
                                <td class="px-4 py-2 text-right">{{ $rupiah($biayaPerpanjang) }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ $rupiah($s->total_harga + $biayaPerpanjang) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                                    Belum ada transaksi untuk filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($daftar->isNotEmpty())
                        <tfoot class="bg-gray-50 font-semibold">
                            <tr>
                                <td class="px-4 py-3" colspan="6">Total ({{ $ringkasan['jumlah'] }} transaksi)</td>
                                <td class="px-4 py-3 text-right">{{ $rupiah($ringkasan['sewa']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $rupiah($ringkasan['perpanjangan']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $rupiah($ringkasan['total']) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endif
    @endif
</x-app-layout>
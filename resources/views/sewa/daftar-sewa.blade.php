<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-800">Daftar Sewa Garasi</h1>
        <p class="mt-1 text-sm text-gray-500">
            Garasi yang sedang disewa hari ini ({{ today()->format('d/m/Y') }}).
        </p>
    </x-slot>

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
                    <th class="px-4 py-3">Total Harga</th>
                    <th class="px-4 py-3">Bukti</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($sewa as $s)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{ $s->garasi_id }}</span><br>
                            <span class="text-xs text-gray-500">{{ $s->garasi->tipe_garasi }}</span>
                        </td>
                        <td class="px-4 py-3">
                            {{ $s->user->nama_lengkap }}<br>
                            <span class="text-xs text-gray-500">{{ $s->user->nomor_telepon }}</span>
                        </td>
                        <td class="px-4 py-3">
                            {{ $s->jenis_kendaraan }}<br>
                            <span class="text-xs text-gray-500">
                                {{ $s->merek_kendaraan }} {{ $s->model_kendaraan }}
                                @if ($s->plat_nomor)
                                    - {{ $s->plat_nomor }}
                                @endif
                            </span>
                            @if ($s->kapasitas_baterai_kwh !== null)
                                <br>
                                <span class="text-xs text-gray-500">
                                    Baterai {{ $s->kapasitas_baterai_kwh }} kWh, awal {{ $s->kwh_awal }} kWh
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            {{ $s->tipe_sewa }}
                            @if ($s->jumlah_hari_sewa)
                                <br><span class="text-xs text-gray-500">{{ $s->jumlah_hari_sewa }} hari</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $s->tanggal_mulai_sewa->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $s->tanggal_akhir_sewa->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($s->total_harga, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ $s->bukti_bayar_url }}" target="_blank" rel="noopener"
                               class="text-indigo-600 underline hover:text-indigo-800">Lihat</a>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('sewa.extend', ['rentalId' => $s->id]) }}"
                               class="px-3 py-1 rounded bg-gray-800 text-white hover:bg-gray-700 whitespace-nowrap">
                                Perpanjang
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                            Tidak ada garasi yang sedang disewa hari ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
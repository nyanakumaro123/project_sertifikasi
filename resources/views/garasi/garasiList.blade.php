<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800">Daftar Garasi</h1>
            <a href="{{ route('garasi.create') }}"
               class="px-4 py-2 rounded-md bg-gray-800 text-white text-sm hover:bg-gray-700">
                + Buat Garasi
            </a>
        </div>
    </x-slot>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Tipe Garasi</th>
                    <th class="px-4 py-3">Harga Harian</th>
                    <th class="px-4 py-3">Harga Isi Listrik per kWh</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($garasi as $g)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $g->id }}</td>
                        <td class="px-4 py-3">{{ $g->tipe_garasi }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($g->harga_harian, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if ($g->harga_isi_listrik_per_kwh !== null)
                                Rp {{ number_format($g->harga_isi_listrik_per_kwh, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($g->isAvailable())
                                <span class="px-2 py-1 rounded bg-green-100 text-green-800">Tersedia</span>
                            @else
                                <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800">Disewa</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            Belum ada garasi. Gunakan tombol "Buat Garasi" untuk menambah.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $garasi->links() }}
    </div>
</x-app-layout>
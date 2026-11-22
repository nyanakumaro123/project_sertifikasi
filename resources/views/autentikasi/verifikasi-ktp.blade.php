<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-800">Verifikasi Penyewa Baru</h1>
    </x-slot>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">NIK</th>
                    <th class="px-4 py-3">Nama Lengkap</th>
                    <th class="px-4 py-3">Nomor Telepon</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($penyewa as $p)
                    <tr>
                        <td class="px-4 py-3">{{ $p->nik }}</td>
                        <td class="px-4 py-3">{{ $p->nama_lengkap }}</td>
                        <td class="px-4 py-3">{{ $p->nomor_telepon }}</td>
                        <td class="px-4 py-3">{{ $p->email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <form method="POST"
                                      action="{{ route('verifikasi-ktp.update', ['userId' => $p->id, 'status' => 'approved']) }}">
                                    @csrf
                                    <button type="submit"
                                            class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700">
                                        Terima
                                    </button>
                                </form>
                                <form method="POST"
                                      action="{{ route('verifikasi-ktp.update', ['userId' => $p->id, 'status' => 'rejected']) }}"
                                      onsubmit="return confirm('Tolak pendaftaran {{ $p->nama_lengkap }}?')">
                                    @csrf
                                    <button type="submit"
                                            class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">
                                        Tolak
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            Tidak ada pendaftar yang menunggu verifikasi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
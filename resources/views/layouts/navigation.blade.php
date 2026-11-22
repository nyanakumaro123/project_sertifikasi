<nav class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                <span class="font-semibold text-gray-800">Sewa Garasi</span>

                @if (Auth::user()->role === 'admin')
                    <x-nav-link :href="route('garasi.index')" :active="request()->routeIs('garasi.index')">
                        Daftar Garasi
                    </x-nav-link>
                    <x-nav-link :href="route('garasi.create')" :active="request()->routeIs('garasi.create')">
                        Buat Garasi
                    </x-nav-link>
                    <x-nav-link :href="route('sewa.index')" :active="request()->routeIs('sewa.*')">
                        Daftar Sewa
                    </x-nav-link>
                    <x-nav-link :href="route('verifikasi-ktp.index')" :active="request()->routeIs('verifikasi-ktp.*')">
                        Verifikasi KTP
                    </x-nav-link>
                    <x-nav-link :href="route('laporan.index')" :active="request()->routeIs('laporan.*')">
                        Laporan
                    </x-nav-link>
                @else
                    <x-nav-link :href="route('sewa.index')" :active="request()->routeIs('sewa.*')">
                        Sewa Garasi
                    </x-nav-link>
                @endif
            </div>

            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-600">
                    {{ Auth::user()->nama_lengkap }}
                    ({{ Auth::user()->role === 'admin' ? 'Admin' : 'Penyewa' }})
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Logout</button>
                </form>
            </div>
        </div>
    </div>
</nav>
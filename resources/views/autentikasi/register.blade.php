<x-guest-layout>
    <h1 class="text-lg font-semibold text-gray-800 mb-4">Daftar sebagai Penyewa</h1>

    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        <div>
            <x-input-label for="nik" value="NIK (Nomor KTP)" />
            <x-text-input id="nik" class="block mt-1 w-full" type="text" name="nik"
                          :value="old('nik')" required autofocus inputmode="numeric" maxlength="16" />
            <x-input-error :messages="$errors->get('nik')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="nama_lengkap" value="Nama Lengkap" />
            <x-text-input id="nama_lengkap" class="block mt-1 w-full" type="text" name="nama_lengkap"
                          :value="old('nama_lengkap')" required />
            <x-input-error :messages="$errors->get('nama_lengkap')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="nomor_telepon" value="Nomor Telepon" />
            <x-text-input id="nomor_telepon" class="block mt-1 w-full" type="text" name="nomor_telepon"
                          :value="old('nomor_telepon')" required inputmode="tel" />
            <x-input-error :messages="$errors->get('nomor_telepon')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="Email (sebagai username)" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                          :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
                          required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Konfirmasi Password" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                          name="password_confirmation" required autocomplete="new-password" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-gray-600 hover:text-gray-900 underline" href="{{ route('login') }}">
                Sudah punya akun?
            </a>
            <x-primary-button>Daftar</x-primary-button>
        </div>
    </form>
</x-guest-layout>
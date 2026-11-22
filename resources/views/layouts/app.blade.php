<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sewa Garasi') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-100 border border-green-300 text-green-800 px-4 py-3 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @error('sewa')
                    <div class="mb-4 rounded-md bg-red-100 border border-red-300 text-red-800 px-4 py-3 text-sm">
                        {{ $message }}
                    </div>
                @enderror

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
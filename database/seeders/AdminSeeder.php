<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // buat dummy data tapi unik
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'password'          => 'password',
                'nama_lengkap'      => 'Administrator',
                'nik'               => '3500000000000001',
                'nomor_telepon'     => '12345678910',
                'role'              => 'admin',
                'status_verifikasi' => 'approved',
            ]
        );
    }
}
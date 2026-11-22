<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Bawaan: Penyewa yang sudah diverifikasi (approved).
     * Password ditulis plain text soalnya model User sudah meng-hash otomatis lewat cast.
     * Buat Testing
     */
    public function definition(): array
    {
        return [
            'email'             => fake()->unique()->safeEmail(),
            'password'          => 'password123',
            'nama_lengkap'      => fake()->name(),
            'nik'               => fake()->unique()->numerify('################'),
            'nomor_telepon'     => fake()->numerify('08##########'),
            'role'              => 'penyewa',
            'status_verifikasi' => 'approved',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status_verifikasi' => 'pending']);
    }
}
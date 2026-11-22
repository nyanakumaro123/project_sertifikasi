<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Register, Login, dan Verifikasi Penyewa Baru (UserController).
 */
class AutentikasiTest extends TestCase
{
    use RefreshDatabase;

    private function dataDaftar(array $ubah = []): array
    {
        return array_merge([
            'nik'                   => '1234567891234567',
            'nama_lengkap'          => 'Tes',
            'nomor_telepon'         => '12345678910',
            'email'                 => 'tes@gmail.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ], $ubah);
    }

    // testing register penyewa

    public function test_penyewa_baru_dapat_mendaftar_dengan_status_menunggu_verifikasi(): void
    {
        $this->post(route('register.store'), $this->dataDaftar())
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Pendaftaran berhasil. Silakan tunggu verifikasi KTP oleh Admin sebelum login.')
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'tes@gmail.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('penyewa', $user->role);
        $this->assertSame('pending', $user->status_verifikasi);
        $this->assertFalse($user->isApproved());
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertGuest();
    }

    public function test_register_dengan_nik_dan_email_yang_sudah_terdaftar_ditolak(): void
    {
        User::factory()->create([
            'nik'   => '1234567891234567',
            'email' => 'tes@gmail.com',
        ]);

        $this->post(route('register.store'), $this->dataDaftar())
            ->assertSessionHasErrors([
                'nik'   => 'NIK sudah terdaftar.',
                'email' => 'Email sudah terdaftar.',
            ]);

        $this->assertSame(1, User::count());
    }

    public function test_register_dengan_konfirmasi_password_berbeda_ditolak(): void
    {
        $this->post(route('register.store'), $this->dataDaftar(['password_confirmation' => 'lain12345']))
            ->assertSessionHasErrors(['password' => 'Konfirmasi password tidak sama.']);

        $this->assertSame(0, User::count());
    }

    // testing login penyewa

    public function test_penyewa_yang_sudah_diterima_dapat_login(): void
    {
        $user = User::factory()->create(['email' => 'tes@gmail.com']);

        $this->post(route('login.store'), ['email' => 'tes@gmail.com', 'password' => 'password123'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_penyewa_yang_masih_pending_tidak_dapat_login(): void
    {
        User::factory()->pending()->create(['email' => 'tes@gmail.com']);

        $this->post(route('login.store'), ['email' => 'tes@gmail.com', 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'Akun Anda masih menunggu verifikasi KTP oleh Admin.']);

        $this->assertGuest();
    }

    public function test_penyewa_yang_ditolak_tidak_dapat_login(): void
    {
        User::factory()->create(['email' => 'tes2@gmail.com', 'status_verifikasi' => 'rejected']);

        $this->post(route('login.store'), ['email' => 'tes2@gmail.com', 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'Pendaftaran Anda ditolak oleh Admin.']);

        $this->assertGuest();
    }

    // testing verifikasi penyewa baru

    public function test_admin_melihat_daftar_pendaftar_yang_masih_pending(): void
    {
        $menunggu = User::factory()->pending()->create(['nama_lengkap' => 'Tes 2']);
        User::factory()->create(['nama_lengkap' => 'Dewi Diterima']);
        User::factory()->create(['nama_lengkap' => 'Eka Ditolak', 'status_verifikasi' => 'rejected']);

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('verifikasi-ktp.index'))
            ->assertOk()
            ->assertViewIs('autentikasi.verifikasi-ktp')
            ->assertViewHas('penyewa', fn ($penyewa) => collect($penyewa)->pluck('id')->all() === [$menunggu->id])
            ->assertSee('Tes 2')
            ->assertDontSee('Dewi Diterima')
            ->assertDontSee('Eka Ditolak');
    }

    public function test_admin_dapat_menerima_pendaftar(): void
    {
        $penyewa = User::factory()->pending()->create(['nama_lengkap' => 'Tes']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('verifikasi-ktp.update', ['userId' => $penyewa->id, 'status' => 'approved']))
            ->assertRedirect()
            ->assertSessionHas('status', 'Penyewa Tes diterima.');

        $this->assertSame('approved', $penyewa->refresh()->status_verifikasi);
        $this->assertTrue($penyewa->isApproved());
    }

    public function test_admin_dapat_menolak_pendaftar(): void
    {
        $penyewa = User::factory()->pending()->create(['nama_lengkap' => 'Tes 2']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('verifikasi-ktp.update', ['userId' => $penyewa->id, 'status' => 'rejected']))
            ->assertRedirect()
            ->assertSessionHas('status', 'Penyewa Tes 2 ditolak.');

        $this->assertSame('rejected', $penyewa->refresh()->status_verifikasi);
    }
}
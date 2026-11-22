<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * bagian untuk user bisa register, tapi statusnya masih pending dan harus diverifikasi admin dulu.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik'           => ['required', 'digits:16', 'unique:users,nik'],
            'nama_lengkap'  => ['required', 'string', 'max:255'],
            'nomor_telepon' => ['required', 'regex:/^[0-9+]{8,20}$/'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'confirmed', 'min:8'],
        ], [
            'nik.digits'          => 'NIK harus terdiri dari 16 digit angka.',
            'nik.unique'          => 'NIK sudah terdaftar.',
            'nomor_telepon.regex' => 'Nomor telepon hanya boleh berisi angka (8-20 digit).',
            'email.unique'        => 'Email sudah terdaftar.',
            'password.confirmed'  => 'Konfirmasi password tidak sama.',
            'password.min'        => 'Password minimal 8 karakter.',
        ]);

        User::create([
            'nik'               => $validated['nik'],
            'nama_lengkap'      => $validated['nama_lengkap'],
            'nomor_telepon'     => $validated['nomor_telepon'],
            'email'             => $validated['email'],
            'password'          => $validated['password'], 
            'role'              => 'penyewa',
            'status_verifikasi' => 'pending',
        ]);

        return redirect()->route('login')
            ->with('status', 'Pendaftaran berhasil. Silakan tunggu verifikasi KTP oleh Admin sebelum login.');
    }

    /**
     * bagian untuk user bisa login.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // kenapa dikasih supaya tidak warning sehingga bisa dikenali, walaupun program tetap bisa jalan
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isApproved()) {
            $status = $user->status_verifikasi;
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => $status === 'rejected'
                    ? 'Pendaftaran Anda ditolak oleh Admin.'
                    : 'Akun Anda masih menunggu verifikasi KTP oleh Admin.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route($user->role === 'admin' ? 'garasi.index' : 'sewa.index');
    }

    /**
     * bagian untuk user bisa logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * bagian untuk verifikasi user dimana admin bisa toal dan approve
     */
    public function verifikasiKTP(Request $request, ?int $userId = null, ?string $status = null): View|RedirectResponse
    {
        if ($userId === null) {
            $penyewa = User::where('role', 'penyewa')
                ->where('status_verifikasi', 'pending')
                ->orderBy('id')
                ->get();

            return view('autentikasi.verifikasi-ktp', compact('penyewa'));
        }

        abort_unless(in_array($status, ['approved', 'rejected'], true), 404);

        $user = User::where('role', 'penyewa')->findOrFail($userId);
        $user->update(['status_verifikasi' => $status]);

        return redirect()->route('verifikasi-ktp.index')
            ->with('status', $status === 'approved'
                ? "Penyewa {$user->nama_lengkap} diterima."
                : "Penyewa {$user->nama_lengkap} ditolak.");
    }
}
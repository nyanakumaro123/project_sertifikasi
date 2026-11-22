<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perpanjang_sewa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informasi_sewa_id')->constrained('informasi_sewa');
            $table->date('tanggal_akhir_lama');
            $table->date('tanggal_akhir_baru');
            $table->decimal('biaya_tambahan', 15, 2);
            $table->string('bukti_bayar_perpanjang_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perpanjang_sewa');
    }
};
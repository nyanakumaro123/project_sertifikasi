<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informasi_sewa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('garasi_id', 4);
            $table->foreign('garasi_id')->references('id')->on('garasi');

            $table->string('tipe_sewa');                              
            $table->string('model_kendaraan');
            $table->string('merek_kendaraan');
            $table->string('jenis_kendaraan');
            $table->string('plat_nomor')->nullable();                 
            $table->double('kwh_awal')->nullable();                   
            $table->double('kapasitas_baterai_kwh')->nullable();      
            $table->unsignedInteger('jumlah_hari_sewa')->nullable();  
            $table->date('tanggal_mulai_sewa');
            $table->date('tanggal_akhir_sewa');
            $table->decimal('total_harga', 15, 2);
            $table->string('bukti_bayar_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informasi_sewa');
    }
};
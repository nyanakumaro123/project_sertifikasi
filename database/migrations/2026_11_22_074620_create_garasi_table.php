<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garasi', function (Blueprint $table) {
            // ID nya bertipe String dan formatnya: S001..S999, C001..C999, E001..E999
            $table->string('id', 4)->primary();
            $table->string('tipe_garasi');                                  
            $table->decimal('harga_harian', 15, 2);
            $table->decimal('harga_isi_listrik_per_kwh', 15, 2)->nullable(); 
            $table->string('status_garasi')->default('available');          
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garasi');
    }
};
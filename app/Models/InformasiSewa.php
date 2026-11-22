<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InformasiSewa extends Model
{
    protected $table = 'informasi_sewa';

    protected $fillable = [
        'user_id',
        'garasi_id',
        'tipe_sewa',
        'model_kendaraan',
        'merek_kendaraan',
        'jenis_kendaraan',
        'plat_nomor',
        'kwh_awal',
        'kapasitas_baterai_kwh',
        'jumlah_hari_sewa',
        'tanggal_mulai_sewa',
        'tanggal_akhir_sewa',
        'total_harga',
        'bukti_bayar_url',
    ];

    protected function casts(): array
    {
        return [
            'kwh_awal' => 'float',
            'kapasitas_baterai_kwh' => 'float',
            'jumlah_hari_sewa' => 'integer',
            'tanggal_mulai_sewa' => 'date',
            'tanggal_akhir_sewa' => 'date',
            'total_harga' => 'float',
        ];
    }

    public function garasi(): BelongsTo
    {
        return $this->belongsTo(Garasi::class, 'garasi_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function perpanjangSewa(): HasMany
    {
        return $this->hasMany(PerpanjangSewa::class, 'informasi_sewa_id', 'id');
    }
}
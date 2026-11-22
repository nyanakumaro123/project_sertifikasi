<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerpanjangSewa extends Model
{
    protected $table = 'perpanjang_sewa';

    protected $fillable = [
        'informasi_sewa_id',
        'tanggal_akhir_lama',
        'tanggal_akhir_baru',
        'biaya_tambahan',
        'bukti_bayar_perpanjang_url',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_akhir_lama' => 'date',
            'tanggal_akhir_baru' => 'date',
            'biaya_tambahan' => 'float',
        ];
    }

    public function informasiSewa(): BelongsTo
    {
        return $this->belongsTo(InformasiSewa::class, 'informasi_sewa_id', 'id');
    }
}
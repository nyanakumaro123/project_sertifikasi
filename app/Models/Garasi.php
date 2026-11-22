<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Garasi extends Model
{
    protected $table = 'garasi';

    // ID yang tipenya String (Contoh: S001, C010, E005), bukan auto-increment
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tipe_garasi',
        'harga_harian',
        'harga_isi_listrik_per_kwh',
        'status_garasi',
    ];

    protected function casts(): array
    {
        return [
            'harga_harian' => 'float',
            'harga_isi_listrik_per_kwh' => 'float',
        ];
    }

    public function isAvailable(): bool
    {
        return $this->status_garasi === 'available';
    }

    public function informasiSewa(): HasMany
    {
        return $this->hasMany(InformasiSewa::class, 'garasi_id', 'id');
    }
}
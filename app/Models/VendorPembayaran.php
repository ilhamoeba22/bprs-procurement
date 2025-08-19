<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPembayaran extends Model
{
    use HasFactory;

    protected $table = 'vendor_pembayaran';
    protected $primaryKey = 'id_pembayaran';
    protected $fillable = [
        'id_pengajuan',
        'nama_vendor',
        'metode_pembayaran',
        'opsi_pembayaran',
        'nominal_dp',
        'tanggal_dp',
        'tanggal_pelunasan',
        'nama_rekening',
        'no_rekening',
        'nama_bank',
        'bukti_dp',
        'bukti_pelunasan',
        'bukti_pajak',
        'bukti_penyelesaian',
        'is_final',
    ];

    protected function casts(): array
    {
        return [
            'bukti_penyelesaian' => 'array',
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'id_pengajuan', 'id_pengajuan');
    }
}

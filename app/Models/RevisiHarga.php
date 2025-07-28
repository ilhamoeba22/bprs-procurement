<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisiHarga extends Model
{
    use HasFactory;

    protected $table = 'revisi_hargas';

    protected $fillable = [
        'survei_harga_id',
        'harga_revisi',
        'opsi_pajak',
        'kondisi_pajak',
        'jenis_pajak',
        'npwp_nik',
        'nama_pemilik_pajak',
        'nominal_pajak',
        'alasan_revisi',
        'bukti_revisi',
        'tanggal_revisi',
        'direvisi_oleh',
        // --- TAMBAHKAN KOLOM BARU DI SINI ---
        'revisi_budget_status_pengadaan',
        'revisi_budget_catatan_pengadaan',
        'revisi_budget_status_perbaikan',
        'revisi_budget_catatan_perbaikan',
        'revisi_budget_approved_by',
        'revisi_kadiv_ga_decision_type',
        'revisi_kadiv_ga_catatan',
        'revisi_kadiv_ga_approved_by',
    ];

    protected $casts = [
        'tanggal_revisi' => 'datetime',
        'harga_revisi' => 'decimal:2',
        'nominal_pajak' => 'decimal:2',
    ];

    /**
     * Get the related SurveiHarga.
     */
    public function surveiHarga(): BelongsTo
    {
        return $this->belongsTo(SurveiHarga::class, 'survei_harga_id');
    }

    /**
     * Get the user who revised the price.
     */
    public function direvisiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direvisi_oleh', 'id_user');
    }

    /**
     * Get the user who approved the budget revision.
     */
    public function revisiBudgetApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisi_budget_approved_by', 'id_user');
    }
    // --- TAMBAHKAN METHOD BARU INI ---
    public function revisiKadivGaApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisi_kadiv_ga_approved_by', 'id_user');
    }
}

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
        'harga_awal',
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
        'revisi_budget_status_pengadaan',
        'revisi_budget_catatan_pengadaan',
        'revisi_budget_status_perbaikan',
        'revisi_budget_catatan_perbaikan',
        'revisi_budget_approved_by',
        'revisi_kadiv_ga_decision_type',
        'revisi_kadiv_ga_catatan',
        'revisi_kadiv_ga_approved_by',
        'revisi_budget_validated_by',
        'revisi_budget_validated_at',
        'revisi_direktur_operasional_decision_type',
        'revisi_direktur_operasional_catatan',
        'revisi_direktur_operasional_approved_by',
        'revisi_direktur_utama_decision_type',
        'revisi_direktur_utama_catatan',
        'revisi_direktur_utama_approved_by',
    ];

    protected $casts = [
        'tanggal_revisi' => 'datetime',
        'harga_revisi' => 'decimal:2',
        'nominal_pajak' => 'decimal:2',
        'revisi_budget_validated_at' => 'datetime',
    ];

    public function surveiHarga(): BelongsTo
    {
        return $this->belongsTo(SurveiHarga::class, 'survei_harga_id');
    }

    public function direvisiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direvisi_oleh', 'id_user');
    }

    public function revisiBudgetApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisi_budget_approved_by', 'id_user');
    }

    public function revisiKadivGaApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisi_kadiv_ga_approved_by', 'id_user');
    }

    public function revisiBudgetValidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisi_budget_validated_by', 'id_user');
    }

    public function revisiDirekturOperasionalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisi_direktur_operasional_approved_by', 'id_user');
    }

    public function revisiDirekturUtamaApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisi_direktur_utama_approved_by', 'id_user');
    }
}

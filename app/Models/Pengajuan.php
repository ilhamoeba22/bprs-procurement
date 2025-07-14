<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pengajuan extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_pengajuan';
    protected $fillable = [
        'kode_pengajuan',
        'id_user_pemohon',
        'status',
        'total_nilai',
        'catatan_revisi',
        'rekomendasi_it_tipe',
        'rekomendasi_it_catatan',
        'budget_status_pengadaan',
        'budget_catatan_pengadaan',
        'budget_status_perbaikan',
        'budget_catatan_perbaikan',
        'kadiv_ga_decision_type',
        'kadiv_ga_catatan',
        'manager_approved_by',
        'kadiv_approved_by',
        'it_recommended_by',
        'ga_surveyed_by',
        'budget_approved_by',
        'kadiv_ops_budget_approved_by',
        'kadiv_ga_approved_by',
        'direktur_operasional_approved_by',
        'direktur_utama_approved_by',
        'disbursed_by',
        'direktur_utama_decision_type',
        'direktur_utama_catatan',
        'direktur_operasional_decision_type',
        'direktur_operasional_catatan',
    ];

    public const STATUS_DRAFT = 'Draft';
    public const STATUS_MENUNGGU_APPROVAL_MANAGER = 'Menunggu Persetujuan Manager';
    public const STATUS_MENUNGGU_APPROVAL_KADIV = 'Menunggu Persetujuan Kepala Divisi';
    public const STATUS_REKOMENDASI_IT = 'Menunggu Rekomendasi IT';
    public const STATUS_SURVEI_GA = 'Proses Survei Harga GA';
    public const STATUS_DISETUJUI = 'Disetujui untuk Pembelian';
    public const STATUS_MENUNGGU_APPROVAL_BUDGET = 'Menunggu Persetujuan Budget';
    public const STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET = 'Menunggu Approval Budget Kadiv Operasional';
    public const STATUS_MENUNGGU_APPROVAL_KADIV_GA = 'Menunggu Approval Kadiv GA';
    public const STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL = 'Menunggu Approval Direktur Operasional';
    public const STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA = 'Menunggu Approval Direktur Utama';
    public const STATUS_MENUNGGU_PENCARIAN_DANA = 'Menunggu Pencarian Dana';
    public const STATUS_SUDAH_BAYAR = 'Sudah Bayar';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DITOLAK_MANAGER = 'Ditolak oleh Manager';
    public const STATUS_DITOLAK_KADIV = 'Ditolak oleh Kepala Divisi';
    public const STATUS_DITOLAK_KADIV_GA = 'Ditolak oleh Kepala Divisi GA';
    public const STATUS_DITOLAK_DIREKTUR_OPERASIONAL = 'Ditolak oleh Direktur Operasional';
    public const STATUS_DITOLAK_DIREKTUR_UTAMA = 'Ditolak oleh Direktur Utama';

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_pemohon', 'id_user');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PengajuanItem::class, 'id_pengajuan', 'id_pengajuan');
    }

    public function approverManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by', 'id_user');
    }

    public function approverKadiv(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kadiv_approved_by', 'id_user');
    }

    public function recommenderIt(): BelongsTo
    {
        return $this->belongsTo(User::class, 'it_recommended_by', 'id_user');
    }

    public function surveyorGa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ga_surveyed_by', 'id_user');
    }

    public function approverBudget(): BelongsTo
    {
        return $this->belongsTo(User::class, 'budget_approved_by', 'id_user');
    }

    public function validatorBudgetOps(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kadiv_ops_budget_approved_by', 'id_user');
    }

    public function approverKadivGa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kadiv_ga_approved_by', 'id_user');
    }

    public function approverDirOps(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direktur_operasional_approved_by', 'id_user');
    }

    public function approverDirUtama(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direktur_utama_approved_by', 'id_user');
    }

    // Metode statis untuk mendapatkan warna badge berdasarkan status
    public static function getStatusBadgeColor($status): string
    {
        Log::debug('Determining badge color for status: ' . $status);

        if (strcasecmp($status, self::STATUS_DRAFT) === 0) {
            return 'gray';
        }

        if (in_array($status, [
            self::STATUS_DITOLAK_MANAGER,
            self::STATUS_DITOLAK_KADIV,
            self::STATUS_DITOLAK_KADIV_GA,
            self::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
            self::STATUS_DITOLAK_DIREKTUR_UTAMA,
        ], true) || stripos($status, 'Ditolak') !== false) {
            return 'danger';
        }

        if (in_array($status, [
            self::STATUS_MENUNGGU_APPROVAL_MANAGER,
            self::STATUS_MENUNGGU_APPROVAL_KADIV,
            self::STATUS_REKOMENDASI_IT,
            self::STATUS_SURVEI_GA,
            self::STATUS_MENUNGGU_APPROVAL_BUDGET,
            self::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
            self::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
            self::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
            self::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
        ], true)) {
            return 'warning';
        }

        if (strcasecmp($status, self::STATUS_MENUNGGU_PENCARIAN_DANA) === 0) {
            return 'info';
        }

        if (strcasecmp($status, self::STATUS_SELESAI) === 0) {
            return 'success';
        }

        // Fallback untuk status tidak dikenali
        return 'warning';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'kadiv_ops_budget_approved_by', // TAMBAHKAN INI
        'kadiv_ga_approved_by',
        'direktur_operasional_approved_by',
        'direktur_utama_approved_by',
        'disbursed_by',
    ];

    public const STATUS_DRAFT = 'Draft';
    public const STATUS_MENUNGGU_APPROVAL_MANAGER = 'Menunggu Persetujuan Manager';
    public const STATUS_MENUNGGU_APPROVAL_KADIV = 'Menunggu Persetujuan Kepala Divisi';
    public const STATUS_REKOMENDASI_IT = 'Menunggu Rekomendasi IT';
    public const STATUS_SURVEI_GA = 'Proses Survei Harga GA';
    public const STATUS_DISETUJUI = 'Disetujui untuk Pembelian';
    public const STATUS_MENUNGGU_APPROVAL_BUDGET = 'Menunggu Persetujuan Budget';
    public const STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET = 'Menunggu Approval Budget Kadiv Operasional'; // TAMBAHKAN STATUS BARU
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
}

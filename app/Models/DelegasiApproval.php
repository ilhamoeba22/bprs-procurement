<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DelegasiApproval extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_delegasi';

    protected $fillable = [
        'id_user_pemberi',
        'id_user_penerima',
        'tipe_halangan',
        'tanggal_mulai',
        'tanggal_selesai',
        'alasan',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'datetime',
            'tanggal_selesai' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function pemberi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_pemberi', 'id_user');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_penerima', 'id_user');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    /**
     * Scope to check if a delegation is currently active.
     */
    public function scopeActiveNow($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('tanggal_mulai', '<=', $now)
            ->where('tanggal_selesai', '>=', $now);
    }

    /**
     * Helper to get status badge text for UI.
     */
    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) {
            return 'Non-aktif';
        }

        $now = now();
        if ($now->lt($this->tanggal_mulai)) {
            return 'Belum Mulai';
        }

        if ($now->gt($this->tanggal_selesai)) {
            return 'Kadaluwarsa';
        }

        return 'Aktif';
    }

    public function getStatusColorAttribute(): string
    {
        if (!$this->is_active) {
            return 'danger';
        }

        $now = now();
        if ($now->lt($this->tanggal_mulai)) {
            return 'warning';
        }

        if ($now->gt($this->tanggal_selesai)) {
            return 'gray';
        }

        return 'success';
    }
}

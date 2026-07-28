<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute; // <-- Tambahkan ini
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * Tentukan primary key jika bukan 'id'.
     */
    protected $primaryKey = 'id_user';

    /**
     * Kolom yang boleh diisi, termasuk nama_user.
     */
    protected $fillable = [
        'nama_user',
        'nik_user',
        'password',
        'id_kantor',
        'id_divisi',
        'id_jabatan',
        'is_active',
    ];

    /**
     * Kolom yang disembunyikan.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Tipe data kolom.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Izin akses ke panel Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    /**
     * Memberitahu Filament cara mendapatkan nama user untuk ditampilkan.
     */
    public function getFilamentName(): string
    {
        return $this->nama_user;
    }

    //==============================================================
    // AKSESOR (JEMBATAN) - KODE PERBAIKAN UTAMA
    //==============================================================
    /**
     * Membuat atribut 'name' virtual.
     * Ini akan membuat model kita kompatibel dengan bagian mana pun
     * dari framework yang mungkin masih mencari atribut 'name' secara default.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->nama_user,
        );
    }
    //==============================================================

    /**
     * Relasi ke Jabatan.
     */
    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'id_kantor', 'id_kantor');
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class, 'id_divisi', 'id_divisi');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan', 'id_jabatan');
    }

    public function delegasiDiberikan()
    {
        return $this->hasMany(DelegasiApproval::class, 'id_user_pemberi', 'id_user');
    }

    public function delegasiDiterima()
    {
        return $this->hasMany(DelegasiApproval::class, 'id_user_penerima', 'id_user');
    }

    /**
     * Get IDs of users who have currently delegated their approval to this user.
     */
    public function getActiveDelegatedPemberiUserIds(): array
    {
        return DelegasiApproval::query()
            ->activeNow()
            ->where('id_user_penerima', $this->id_user)
            ->pluck('id_user_pemberi')
            ->toArray();
    }

    /**
     * Get Collection of User models for users who delegated their approval to this user.
     */
    public function getActiveDelegatedPemberiUsers()
    {
        $ids = $this->getActiveDelegatedPemberiUserIds();
        return User::whereIn('id_user', $ids)->get();
    }

    /**
     * Check if user is HRD (Staff HRD / Kadiv HR) or Super Admin.
     */
    public function isHrdOrAdmin(): bool
    {
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        $namaJabatan = strtolower($this->jabatan?->nama_jabatan ?? '');
        if (str_contains($namaJabatan, 'hrd') || str_contains($namaJabatan, 'hr,') || str_contains($namaJabatan, 'human resource')) {
            return true;
        }

        return $this->hasAnyRole(['Staff HRD', 'HRD', 'HR']);
    }
}

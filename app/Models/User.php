<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
     * Koneksi ke database SSO (sumber kebenaran user).
     */
    protected $connection = 'sso';

    /**
     * Primary key sesuai konvensi SSO.
     */
    protected $primaryKey = 'id';

    /**
     * Kolom yang boleh diisi.
     */
    protected $fillable = [
        'nama_user',
        'nik_user',
        'password',
        'kantor_id',
        'divisi_id',
        'jabatan_id',
        'email',
        'phone_number',
        'avatar',
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
        return true;
    }

    /**
     * Memberitahu Filament cara mendapatkan nama user untuk ditampilkan.
     */
    public function getFilamentName(): string
    {
        return $this->nama_user;
    }

    /**
     * Aksesor 'name' virtual - kompatibilitas dengan framework.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->nama_user,
        );
    }

    // ==========================================
    // Relationships (konvensi FK SSO)
    // ==========================================

    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'kantor_id');
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }
}

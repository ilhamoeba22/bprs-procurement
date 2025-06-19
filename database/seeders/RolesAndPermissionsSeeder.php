<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache agar tidak terjadi error
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // === DEFINISI SEMUA IZIN (PERMISSIONS) ===
        Permission::firstOrCreate(['name' => 'buat pengajuan']);
        Permission::firstOrCreate(['name' => 'lihat semua pengajuan']);
        Permission::firstOrCreate(['name' => 'kelola master data']);

        // Izin per tahap
        Permission::firstOrCreate(['name' => 'approval manager']);
        Permission::firstOrCreate(['name' => 'approval kadiv']);
        Permission::firstOrCreate(['name' => 'rekomendasi it']);
        Permission::firstOrCreate(['name' => 'survei harga ga']);
        Permission::firstOrCreate(['name' => 'approval budget']);
        Permission::firstOrCreate(['name' => 'finalisasi keputusan ga']);
        Permission::firstOrCreate(['name' => 'approval direktur operasional']);
        Permission::firstOrCreate(['name' => 'approval direktur utama']);
        Permission::firstOrCreate(['name' => 'pencairan dana']);

        // === DEFINISI SEMUA PERAN (ROLES) & PEMBERIAN IZIN ===
        Role::firstOrCreate(['name' => 'Staff'])->givePermissionTo('buat pengajuan');
        Role::firstOrCreate(['name' => 'Team Leader'])->givePermissionTo('buat pengajuan');
        Role::firstOrCreate(['name' => 'Manager'])->givePermissionTo(['buat pengajuan', 'approval manager']);
        Role::firstOrCreate(['name' => 'Kepala Divisi'])->givePermissionTo(['buat pengajuan', 'approval kadiv']);
        Role::firstOrCreate(['name' => 'Kepala Divisi IT'])->givePermissionTo(['buat pengajuan', 'rekomendasi it']);
        Role::firstOrCreate(['name' => 'General Affairs'])->givePermissionTo('survei harga ga');
        Role::firstOrCreate(['name' => 'Tim Budgeting'])->givePermissionTo('approval budget');
        // PERBAIKAN DI SINI: Menggunakan izin baru yang lebih sesuai
        Role::firstOrCreate(['name' => 'Kepala Divisi GA'])->givePermissionTo(['buat pengajuan', 'approval kadiv', 'finalisasi keputusan ga']);
        Role::firstOrCreate(['name' => 'Direktur Operasional'])->givePermissionTo(['buat pengajuan', 'approval direktur operasional', 'lihat semua pengajuan']);
        Role::firstOrCreate(['name' => 'Direktur Utama'])->givePermissionTo(['buat pengajuan', 'approval direktur utama', 'lihat semua pengajuan']);
        Role::firstOrCreate(['name' => 'Kepala Divisi Operasional'])->givePermissionTo('pencairan dana');

        // Peran Super Admin bisa melakukan segalanya
        Role::firstOrCreate(['name' => 'Super Admin'])->givePermissionTo(Permission::all());
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DivisiSeeder::class,
            JabatanSeeder::class,
            UserSeeder::class,
            // PengajuanSeeder::class,
            // PengecatanKantorSeeder::class,
            // PengadaanSewaSeeder::class,
            // PengadaanITSeeder::class,
            // SkenarioNilaiSeeder::class,
        ]);
    }
}

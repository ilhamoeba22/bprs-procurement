<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Jabatan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $jabatanSuperAdmin = Jabatan::where('nama_jabatan', 'System Administrator')->first();
        if ($jabatanSuperAdmin) {
            $userSuperAdmin = User::create([
                'nama_user' => 'Super Admin',
                'nik_user' => 'superadmin',
                'password' => Hash::make('password'),
                'id_jabatan' => $jabatanSuperAdmin->id_jabatan,
                'id_divisi' => $jabatanSuperAdmin->id_divisi,
                'id_kantor' => $jabatanSuperAdmin->id_kantor,
            ]);
            $userSuperAdmin->assignRole('Super Admin');
        }

        // === 2. BUAT SEMUA PENGGUNA LAINNYA ===
        $users = [
            ['nama_user' => 'Ahmad Mustofa Salim', 'nik_user' => 'C00125', 'jabatan_nama' => 'Account Officer UMKM', 'role_nama' => 'Staff'],
            ['nama_user' => 'Dwi Ari Zufriyani', 'nik_user' => '431891', 'jabatan_nama' => 'Funding Officer Retail', 'role_nama' => 'Staff'],
            ['nama_user' => 'Setio Ariyanto', 'nik_user' => '451881', 'jabatan_nama' => 'Manager Pembiayaan', 'role_nama' => 'Manager'],
            ['nama_user' => 'Nuryasin', 'nik_user' => 'C00324', 'jabatan_nama' => 'Account Officer UMKM', 'role_nama' => 'Staff'],
            ['nama_user' => 'Hani Budi Lestari', 'nik_user' => 'C00124', 'jabatan_nama' => 'Account Officer UMKM', 'role_nama' => 'Staff'],
            ['nama_user' => 'Muchammad Erwin Setyawan', 'nik_user' => '120877', 'jabatan_nama' => 'Account Officer Retail', 'role_nama' => 'Staff'],
            ['nama_user' => 'Anna Maria Sandri', 'nik_user' => '501974', 'jabatan_nama' => 'Team Leader Funding', 'role_nama' => 'Team Leader'],
            ['nama_user' => 'Ardi Alamsyah Sutedja', 'nik_user' => '872193', 'jabatan_nama' => 'Account Officer UMKM', 'role_nama' => 'Staff'],
            ['nama_user' => 'Imam Suwandi', 'nik_user' => '982297', 'jabatan_nama' => 'Account Officer UMKM', 'role_nama' => 'Staff'],
            ['nama_user' => 'Inneke Febrihardianti Syamsi', 'nik_user' => '72397', 'jabatan_nama' => 'Funding Officer Corporate', 'role_nama' => 'Staff'],
            ['nama_user' => 'Hafizon Ramadhan', 'nik_user' => '62396', 'jabatan_nama' => 'Funding Officer Retail', 'role_nama' => 'Staff'],
            ['nama_user' => 'Belinda Paramasiddha Jannah', 'nik_user' => '812195', 'jabatan_nama' => 'Account Officer Retail', 'role_nama' => 'Staff'],
            ['nama_user' => 'Faradays Muhammad', 'nik_user' => '42383', 'jabatan_nama' => 'Kepala Divisi Bisnis', 'role_nama' => 'Kepala Divisi'],
            ['nama_user' => 'Andris Arisyandi', 'nik_user' => '732079', 'jabatan_nama' => 'Team Leader ZIS, WAF & Social Media', 'role_nama' => 'Team Leader'],
            ['nama_user' => 'Tariman', 'nik_user' => '922295', 'jabatan_nama' => 'Funding Officer Retail', 'role_nama' => 'Staff'],
            ['nama_user' => 'Syafiqunnur', 'nik_user' => '621995', 'jabatan_nama' => 'Staff Admin Corporate Secretary', 'role_nama' => 'Staff'],
            ['nama_user' => 'Fitri Rini Farida', 'nik_user' => '511978', 'jabatan_nama' => 'Kepala Divisi Corporate Secretary', 'role_nama' => 'Kepala Divisi'],
            ['nama_user' => 'Pudra Fanki Amrillah', 'nik_user' => '32393', 'jabatan_nama' => 'Staf Design Grafis', 'role_nama' => 'Staff'],
            ['nama_user' => 'Kholid', 'nik_user' => '882180', 'jabatan_nama' => 'Direktur Utama', 'role_nama' => 'Direktur Utama'],
            ['nama_user' => 'Mushoniful Agustian', 'nik_user' => '561974', 'jabatan_nama' => 'Direktur Operasional & Kepatuhan', 'role_nama' => 'Direktur Operasional'],
            ['nama_user' => 'Afif Ridho Rahmanto', 'nik_user' => '932298', 'jabatan_nama' => 'Staff Appraisal', 'role_nama' => 'Staff'],
            ['nama_user' => 'Catur Eko Wahyono', 'nik_user' => '942295', 'jabatan_nama' => 'Office Boy', 'role_nama' => 'Staff'],
            ['nama_user' => 'Winda Salsabilla Kris Daldiri', 'nik_user' => '902291', 'jabatan_nama' => 'Staff Admin Legal', 'role_nama' => 'Staff'],
            ['nama_user' => 'Amrozi Arya Bima', 'nik_user' => '892196', 'jabatan_nama' => 'Security', 'role_nama' => 'Staff'],
            ['nama_user' => 'Christianto Bambang Suwarjo', 'nik_user' => '181592', 'jabatan_nama' => 'Driver', 'role_nama' => 'Staff'],
            ['nama_user' => 'Rinto Widodo', 'nik_user' => '972291', 'jabatan_nama' => 'Office Boy', 'role_nama' => 'Staff'],
            ['nama_user' => 'Erwanto', 'nik_user' => '201685', 'jabatan_nama' => 'Security', 'role_nama' => 'Staff'],
            ['nama_user' => 'Achmad Syihab Arya Satya', 'nik_user' => 'C00424', 'jabatan_nama' => 'Staff GA', 'role_nama' => 'General Affairs'],
            ['nama_user' => 'Muhammad Sanhaji', 'nik_user' => 'C00823', 'jabatan_nama' => 'Staff HRD', 'role_nama' => 'Staff'],
            ['nama_user' => 'Untung Sugana', 'nik_user' => '571977', 'jabatan_nama' => 'Security', 'role_nama' => 'Staff'],
            ['nama_user' => 'Pandu Fitri Andika', 'nik_user' => '772085', 'jabatan_nama' => 'Security', 'role_nama' => 'Staff'],
            ['nama_user' => 'Sugeng Setyanto', 'nik_user' => '22590', 'jabatan_nama' => 'Office Boy', 'role_nama' => 'Staff'],
            ['nama_user' => 'Budi Pramana', 'nik_user' => '601980', 'jabatan_nama' => 'Office Boy', 'role_nama' => 'Staff'],
            ['nama_user' => 'Arryanto Hendratama', 'nik_user' => '692078', 'jabatan_nama' => 'Manager Legal', 'role_nama' => 'Manager'],
            ['nama_user' => 'Satya Puguh Toh Jiwo', 'nik_user' => '682089', 'jabatan_nama' => 'Driver', 'role_nama' => 'Staff'],
            ['nama_user' => 'Teddy Sutrisna', 'nik_user' => '411881', 'jabatan_nama' => 'Kepala Divisi HR, GA dan Legal', 'role_nama' => 'Kepala Divisi GA'],
            ['nama_user' => 'Muhammad Ilham Pratama', 'nik_user' => 'C00524', 'jabatan_nama' => 'Staff IT & MIS', 'role_nama' => 'Staff'],
            ['nama_user' => 'Alfian Akbar Prasetya', 'nik_user' => '551996', 'jabatan_nama' => 'Team Leader IT & MIS', 'role_nama' => 'Team Leader'],
            ['nama_user' => 'Prabawa Rahmat Ismail', 'nik_user' => '251785', 'jabatan_nama' => 'Kadiv IT, MIS & Product Development', 'role_nama' => 'Kepala Divisi IT'],
            ['nama_user' => 'Chandra Widya Mahardika', 'nik_user' => '82490', 'jabatan_nama' => 'Pejabat Eksekutif Manajemen Risiko', 'role_nama' => 'Kepala Divisi'],
            ['nama_user' => 'Eis Kristina Yusanti', 'nik_user' => '171676', 'jabatan_nama' => 'Staff Accounting', 'role_nama' => 'Tim Budgeting'],
            ['nama_user' => 'Rianita Indah Dwi Arum', 'nik_user' => '381894', 'jabatan_nama' => 'Staff Operasional', 'role_nama' => 'Staff'],
            ['nama_user' => 'Jihan Satya Meinisa', 'nik_user' => '531996', 'jabatan_nama' => 'Teller', 'role_nama' => 'Tim Budgeting'],
            ['nama_user' => 'Nurten Novita Sari', 'nik_user' => '70981', 'jabatan_nama' => 'Kepala Divisi Operasional', 'role_nama' => 'Kepala Divisi Operasional'],
            ['nama_user' => 'Pratiwi Budi Setyaningsih', 'nik_user' => '12592', 'jabatan_nama' => 'Staff Accounting', 'role_nama' => 'Staff'],
            ['nama_user' => 'Nadhofa Aulia Nur Arifien', 'nik_user' => '842196', 'jabatan_nama' => 'Customer Service', 'role_nama' => 'Staff'],
            ['nama_user' => 'Partiyah', 'nik_user' => '161596', 'jabatan_nama' => 'Head Teller & Customer Service', 'role_nama' => 'Tim Budgeting'],
            ['nama_user' => 'Imam Muhajirin', 'nik_user' => '661993', 'jabatan_nama' => 'Staff Operasional', 'role_nama' => 'Staff'],
            ['nama_user' => 'Dodi Herdiatmo', 'nik_user' => '271774', 'jabatan_nama' => 'Staf Asset Mangement Unit (AMU)', 'role_nama' => 'Kepala Divisi'],
            ['nama_user' => 'Heriyanto', 'nik_user' => '361876', 'jabatan_nama' => 'Staff Collection & Remedial', 'role_nama' => 'Staff'],
            ['nama_user' => 'Fahrul Hijar', 'nik_user' => '611980', 'jabatan_nama' => 'Team Leader Collection & Remedial', 'role_nama' => 'Team Leader'],
            ['nama_user' => 'Rio Dewangga', 'nik_user' => '952291', 'jabatan_nama' => 'Staff Satuan Kerja Audit Internal (SKAI)', 'role_nama' => 'Staff'],
            ['nama_user' => 'Sesilia Lilies Andriani', 'nik_user' => '802174', 'jabatan_nama' => 'Kepala Satuan Kerja Audit Internal (SKAI)', 'role_nama' => 'Kepala Divisi'],
            ['nama_user' => 'Winasista Salarina', 'nik_user' => '2292', 'jabatan_nama' => 'Kepala Satuan Kerja Kepatuhan & APU-PPT', 'role_nama' => 'Kepala Divisi'],
        ];

        foreach ($users as $userData) {
            $jabatan = Jabatan::where('nama_jabatan', $userData['jabatan_nama'])->first();

            if ($jabatan) {
                $user = User::create([
                    'nama_user' => $userData['nama_user'],
                    'nik_user' => $userData['nik_user'],
                    'password' => Hash::make($userData['nik_user']), // Password sama dengan NIK
                    'id_jabatan' => $jabatan->id_jabatan,
                    'id_divisi' => $jabatan->id_divisi,
                    'id_kantor' => $jabatan->id_kantor,
                ]);

                $user->assignRole($userData['role_nama']);
            }
        }
    }
}

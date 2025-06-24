<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            // Hapus kolom lama jika ada
            if (Schema::hasColumn('pengajuans', 'budget_status')) {
                $table->dropColumn('budget_status');
            }
            if (Schema::hasColumn('pengajuans', 'budget_catatan')) {
                $table->dropColumn('budget_catatan');
            }

            // Tambahkan kolom baru untuk setiap skenario budget
            $table->string('budget_status_pengadaan')->nullable()->after('rekomendasi_it_catatan');
            $table->text('budget_catatan_pengadaan')->nullable()->after('budget_status_pengadaan');
            $table->string('budget_status_perbaikan')->nullable()->after('budget_catatan_pengadaan');
            $table->text('budget_catatan_perbaikan')->nullable()->after('budget_status_perbaikan');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn([
                'budget_status_pengadaan',
                'budget_catatan_pengadaan',
                'budget_status_perbaikan',
                'budget_catatan_perbaikan'
            ]);
        });
    }
};

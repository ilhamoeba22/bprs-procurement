<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePengajuansBudgetColumns extends Migration
{
    public function up()
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            // Hapus kolom lama
            $table->dropColumn([
                'budget_status_pengadaan',
                'budget_catatan_pengadaan',
                'budget_status_perbaikan',
                'budget_catatan_perbaikan'
            ]);
            // Tambahkan kolom baru
            $table->string('status_budget')->nullable()->after('rekomendasi_it_catatan');
            $table->text('catatan_budget')->nullable()->after('status_budget');
        });
    }

    public function down()
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            // Tambahkan kembali kolom lama untuk rollback
            $table->string('budget_status_pengadaan')->nullable();
            $table->text('budget_catatan_pengadaan')->nullable();
            $table->string('budget_status_perbaikan')->nullable();
            $table->text('budget_catatan_perbaikan')->nullable();
            // Hapus kolom baru
            $table->dropColumn(['status_budget', 'catatan_budget']);
        });
    }
}

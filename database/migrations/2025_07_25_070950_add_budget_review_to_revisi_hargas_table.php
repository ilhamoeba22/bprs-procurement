<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            // Menambahkan kolom untuk menyimpan hasil review budget revisi
            $table->string('revisi_budget_status_pengadaan')->nullable()->after('bukti_revisi');
            $table->text('revisi_budget_catatan_pengadaan')->nullable()->after('revisi_budget_status_pengadaan');
            $table->string('revisi_budget_status_perbaikan')->nullable()->after('revisi_budget_catatan_pengadaan');
            $table->text('revisi_budget_catatan_perbaikan')->nullable()->after('revisi_budget_status_perbaikan');
            $table->foreignId('revisi_budget_approved_by')->nullable()->constrained('users', 'id_user')->after('revisi_budget_catatan_perbaikan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->dropForeign(['revisi_budget_approved_by']);
            $table->dropColumn([
                'revisi_budget_status_pengadaan',
                'revisi_budget_catatan_pengadaan',
                'revisi_budget_status_perbaikan',
                'revisi_budget_catatan_perbaikan',
                'revisi_budget_approved_by',
            ]);
        });
    }
};

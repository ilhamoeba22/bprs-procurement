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
        // 1. Normalize pengajuans table
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->renameColumn('id_pengajuan', 'id');
            $table->renameColumn('id_user_pemohon', 'pemohon_id');
            $table->renameColumn('manager_approved_by', 'manager_id');
            $table->renameColumn('kadiv_approved_by', 'kadiv_id');
            $table->renameColumn('it_recommended_by', 'it_id');
            $table->renameColumn('ga_surveyed_by', 'ga_id');
            $table->renameColumn('budget_approved_by', 'budget_id');
            $table->renameColumn('kadiv_ops_budget_approved_by', 'kadiv_ops_id');
            $table->renameColumn('kadiv_ga_approved_by', 'kadiv_ga_id');
            $table->renameColumn('direktur_operasional_approved_by', 'dirop_id');
            $table->renameColumn('direktur_utama_approved_by', 'dirut_id');
            $table->renameColumn('disbursed_by', 'disburser_id');
        });

        // 2. Normalize pengajuan_items table
        Schema::table('pengajuan_items', function (Blueprint $table) {
            $table->renameColumn('id_pengajuan', 'pengajuan_id');
        });

        // 3. Normalize survei_hargas table
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->renameColumn('id_item', 'item_id');
        });

        // 4. Normalize vendor_pembayaran table
        Schema::table('vendor_pembayaran', function (Blueprint $table) {
            $table->renameColumn('id_pengajuan', 'pengajuan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->renameColumn('id', 'id_pengajuan');
            $table->renameColumn('pemohon_id', 'id_user_pemohon');
            $table->renameColumn('manager_id', 'manager_approved_by');
            $table->renameColumn('kadiv_id', 'kadiv_approved_by');
            $table->renameColumn('it_id', 'it_recommended_by');
            $table->renameColumn('ga_id', 'ga_surveyed_by');
            $table->renameColumn('budget_id', 'budget_approved_by');
            $table->renameColumn('kadiv_ops_id', 'kadiv_ops_budget_approved_by');
            $table->renameColumn('kadiv_ga_id', 'kadiv_ga_approved_by');
            $table->renameColumn('dirop_id', 'direktur_operasional_approved_by');
            $table->renameColumn('dirut_id', 'direktur_utama_approved_by');
            $table->renameColumn('disburser_id', 'disbursed_by');
        });

        Schema::table('pengajuan_items', function (Blueprint $table) {
            $table->renameColumn('pengajuan_id', 'id_pengajuan');
        });

        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->renameColumn('item_id', 'id_item');
        });

        Schema::table('vendor_pembayaran', function (Blueprint $table) {
            $table->renameColumn('pengajuan_id', 'id_pengajuan');
        });
    }
};

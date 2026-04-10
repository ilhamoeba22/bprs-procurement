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
            $table->renameColumn('revisi_budget_approved_by', 'revisi_budget_id');
            $table->renameColumn('revisi_kadiv_ga_approved_by', 'revisi_kadiv_ga_id');
            $table->renameColumn('revisi_budget_validated_by', 'revisi_budget_validator_id');
            $table->renameColumn('revisi_direktur_operasional_approved_by', 'revisi_dirop_id');
            $table->renameColumn('revisi_direktur_utama_approved_by', 'revisi_dirut_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->renameColumn('revisi_budget_id', 'revisi_budget_approved_by');
            $table->renameColumn('revisi_kadiv_ga_id', 'revisi_kadiv_ga_approved_by');
            $table->renameColumn('revisi_budget_validator_id', 'revisi_budget_validated_by');
            $table->renameColumn('revisi_dirop_id', 'revisi_direktur_operasional_approved_by');
            $table->renameColumn('revisi_dirut_id', 'revisi_direktur_utama_approved_by');
        });
    }
};

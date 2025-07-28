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
            // Menambahkan kolom untuk menyimpan hasil approval revisi oleh Kadiv GA
            $table->string('revisi_kadiv_ga_decision_type')->nullable()->after('revisi_budget_approved_by');
            $table->text('revisi_kadiv_ga_catatan')->nullable()->after('revisi_kadiv_ga_decision_type');
            $table->foreignId('revisi_kadiv_ga_approved_by')->nullable()->constrained('users', 'id_user')->after('revisi_kadiv_ga_catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->dropForeign(['revisi_kadiv_ga_approved_by']);
            $table->dropColumn([
                'revisi_kadiv_ga_decision_type',
                'revisi_kadiv_ga_catatan',
                'revisi_kadiv_ga_approved_by',
            ]);
        });
    }
};

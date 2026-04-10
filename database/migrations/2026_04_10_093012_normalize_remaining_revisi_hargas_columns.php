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
            // Adding missing columns for Kadiv Ops decisions in revisions
            $table->string('revisi_kadiv_ops_decision_type')->nullable()->after('revisi_kadiv_ga_id');
            $table->text('revisi_kadiv_ops_catatan')->nullable()->after('revisi_kadiv_ops_decision_type');
            $table->unsignedBigInteger('revisi_kadiv_ops_id')->nullable()->after('revisi_kadiv_ops_catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->dropColumn(['revisi_kadiv_ops_decision_type', 'revisi_kadiv_ops_catatan', 'revisi_kadiv_ops_id']);
        });
    }
};

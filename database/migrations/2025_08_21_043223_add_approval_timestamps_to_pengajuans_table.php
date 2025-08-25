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
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->timestamp('kadiv_ga_approved_at')->nullable()->after('kadiv_ga_approved_by');
            $table->timestamp('direktur_operasional_approved_at')->nullable()->after('direktur_operasional_approved_by');
            $table->timestamp('direktur_utama_approved_at')->nullable()->after('direktur_utama_approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn('kadiv_ga_approved_at');
            $table->dropColumn('direktur_operasional_approved_at');
            $table->dropColumn('direktur_utama_approved_at');
        });
    }
};

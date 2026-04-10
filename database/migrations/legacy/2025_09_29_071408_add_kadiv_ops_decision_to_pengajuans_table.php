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
            $table->string('kadiv_ops_decision_type')->nullable()->after('direktur_operasional_catatan');
            $table->text('kadiv_ops_catatan')->nullable()->after('kadiv_ops_decision_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            // v-- TAMBAHKAN DUA BARIS INI --v
            $table->dropColumn(['kadiv_ops_decision_type', 'kadiv_ops_catatan']);
        });
    }
};

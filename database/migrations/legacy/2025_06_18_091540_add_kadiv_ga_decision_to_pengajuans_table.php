<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('kadiv_ga_decision_type')->nullable()->after('budget_catatan'); // Menyimpan 'Pengadaan' atau 'Perbaikan'
            $table->text('kadiv_ga_catatan')->nullable()->after('kadiv_ga_decision_type');
        });
    }
    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['kadiv_ga_decision_type', 'kadiv_ga_catatan']);
        });
    }
};

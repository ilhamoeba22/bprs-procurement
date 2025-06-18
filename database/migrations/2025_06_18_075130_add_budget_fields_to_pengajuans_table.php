<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('budget_status')->nullable()->after('rekomendasi_it_catatan');
            $table->text('budget_catatan')->nullable()->after('budget_status');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['budget_status', 'budget_catatan']);
        });
    }
};

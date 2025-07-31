<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->dropColumn('catatan_validasi');
        });
    }

    public function down(): void
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->text('catatan_validasi')->nullable()->after('revisi_budget_validated_at');
        });
    }
};

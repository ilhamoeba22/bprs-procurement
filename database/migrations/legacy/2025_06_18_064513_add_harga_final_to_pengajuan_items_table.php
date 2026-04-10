<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pengajuan_items', function (Blueprint $table) {
            $table->decimal('harga_final', 15, 2)->nullable()->after('justifikasi');
        });
    }
    public function down(): void
    {
        Schema::table('pengajuan_items', function (Blueprint $table) {
            $table->dropColumn('harga_final');
        });
    }
};

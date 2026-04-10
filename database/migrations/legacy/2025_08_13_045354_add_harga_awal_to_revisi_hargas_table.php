<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->decimal('harga_awal', 15, 2)->nullable()->after('survei_harga_id');
        });
    }
    public function down(): void
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->dropColumn('harga_awal');
        });
    }
};

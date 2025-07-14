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
        Schema::table('survei_hargas', function (Blueprint $table) {
            // Mengubah kolom bukti_path agar bisa menerima nilai NULL
            $table->string('bukti_path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            //
        });
    }
};

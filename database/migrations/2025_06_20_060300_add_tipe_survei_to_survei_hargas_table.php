<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->string('tipe_survei')->after('id_item');
        });
    }

    public function down(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->dropColumn('tipe_survei');
        });
    }
};

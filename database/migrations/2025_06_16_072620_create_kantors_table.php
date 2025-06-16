<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kantors', function (Blueprint $table) {
            $table->id('id_kantor');
            $table->string('nama_kantor');
            $table->text('alamat_kantor');
            $table->string('kode_kantor')->unique();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('kantors');
    }
};

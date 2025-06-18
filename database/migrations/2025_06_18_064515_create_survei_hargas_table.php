<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survei_hargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_item')->constrained('pengajuan_items', 'id_item')->onDelete('cascade');
            $table->string('nama_vendor');
            $table->decimal('harga', 15, 2);
            $table->string('bukti_path'); // Path ke file bukti yang di-upload
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('survei_hargas');
    }
};

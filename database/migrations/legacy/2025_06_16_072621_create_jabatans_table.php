<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id('id_jabatan');
            $table->string('nama_jabatan');
            $table->string('type_jabatan')->nullable();
            $table->foreignId('id_kantor')->constrained('kantors', 'id_kantor')->onDelete('cascade');
            $table->foreignId('id_divisi')->constrained('divisis', 'id_divisi')->onDelete('cascade');
            $table->foreignId('acc_jabatan_id')->nullable()->constrained('jabatans', 'id_jabatan')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('jabatans');
    }
};

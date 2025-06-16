<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('nama_user');
            $table->string('nik_user')->unique();
            $table->string('password');
            $table->foreignId('id_kantor')->nullable()->constrained('kantors', 'id_kantor')->nullOnDelete();
            $table->foreignId('id_divisi')->nullable()->constrained('divisis', 'id_divisi')->nullOnDelete();
            $table->foreignId('id_jabatan')->nullable()->constrained('jabatans', 'id_jabatan')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users', 'id_user')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};

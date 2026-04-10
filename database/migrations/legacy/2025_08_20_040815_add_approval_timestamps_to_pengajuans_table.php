<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->timestamp('manager_approved_at')->nullable()->after('manager_approved_by');
            $table->timestamp('kadiv_approved_at')->nullable()->after('kadiv_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['manager_approved_at', 'kadiv_approved_at']);
        });
    }
};

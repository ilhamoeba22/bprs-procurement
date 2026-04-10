<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->timestamp('budget_approved_at')->nullable()->after('budget_approved_by');
            $table->timestamp('kadiv_ops_budget_approved_at')->nullable()->after('kadiv_ops_budget_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['budget_approved_at', 'kadiv_ops_budget_approved_at']);
        });
    }
};

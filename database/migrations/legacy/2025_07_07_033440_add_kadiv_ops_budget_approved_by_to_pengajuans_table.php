<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->unsignedBigInteger('kadiv_ops_budget_approved_by')->nullable()->after('budget_approved_by');
            $table->foreign('kadiv_ops_budget_approved_by')->references('id_user')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            //
        });
    }
};

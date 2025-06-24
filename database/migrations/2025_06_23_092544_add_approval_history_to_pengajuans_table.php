<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->foreignId('manager_approved_by')->nullable()->after('kadiv_ga_catatan')->constrained('users', 'id_user')->nullOnDelete();
            $table->foreignId('kadiv_approved_by')->nullable()->after('manager_approved_by')->constrained('users', 'id_user')->nullOnDelete();
            $table->foreignId('it_recommended_by')->nullable()->after('kadiv_approved_by')->constrained('users', 'id_user')->nullOnDelete();
            $table->foreignId('ga_surveyed_by')->nullable()->after('it_recommended_by')->constrained('users', 'id_user')->nullOnDelete();
            $table->foreignId('budget_approved_by')->nullable()->after('ga_surveyed_by')->constrained('users', 'id_user')->nullOnDelete();
            $table->foreignId('kadiv_ga_approved_by')->nullable()->after('budget_approved_by')->constrained('users', 'id_user')->nullOnDelete();
            $table->foreignId('direktur_operasional_approved_by')->nullable()->after('kadiv_ga_approved_by')->constrained('users', 'id_user')->nullOnDelete();
            $table->foreignId('direktur_utama_approved_by')->nullable()->after('direktur_operasional_approved_by')->constrained('users', 'id_user')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropForeign(['manager_approved_by']);
            // ... tambahkan dropForeign lainnya jika perlu
            $table->dropColumn([
                'manager_approved_by',
                'kadiv_approved_by',
                'it_recommended_by',
                'ga_surveyed_by',
                'budget_approved_by',
                'kadiv_ga_approved_by',
                'direktur_operasional_approved_by',
                'direktur_utama_approved_by'
            ]);
        });
    }
};

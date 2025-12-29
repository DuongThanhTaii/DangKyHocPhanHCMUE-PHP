<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chi_tiet_lich_su_dang_ky', function (Blueprint $table) {
            $table->uuid('lop_hoc_phan_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chi_tiet_lich_su_dang_ky', function (Blueprint $table) {
            $table->dropColumn('lop_hoc_phan_id');
        });
    }
};

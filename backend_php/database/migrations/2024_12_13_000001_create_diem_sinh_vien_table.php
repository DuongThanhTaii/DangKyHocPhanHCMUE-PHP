<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Table: diem_sinh_vien - Stores student grades for class sections
     */
    public function up(): void
    {
        Schema::create('diem_sinh_vien', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sinh_vien_id');
            $table->uuid('lop_hoc_phan_id');
            $table->decimal('diem_chuyen_can', 4, 2)->nullable(); // Attendance score
            $table->decimal('diem_giua_ky', 4, 2)->nullable();    // Midterm score
            $table->decimal('diem_cuoi_ky', 4, 2)->nullable();    // Final score
            $table->decimal('diem_tong_ket', 4, 2)->nullable();   // Overall score
            $table->text('ghi_chu')->nullable();                   // Notes

            // Indexes for better query performance
            $table->index('sinh_vien_id');
            $table->index('lop_hoc_phan_id');

            // Unique constraint: one grade record per student per class
            $table->unique(['sinh_vien_id', 'lop_hoc_phan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diem_sinh_vien');
    }
};

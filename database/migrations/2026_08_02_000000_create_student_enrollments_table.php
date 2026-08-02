<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_status_id')->constrained()->restrictOnDelete();
            $table->date('enrolled_at');
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
            $table->index(['classroom_id', 'academic_year_id']);
            $table->index(['academic_year_id', 'student_status_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};

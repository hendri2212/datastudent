<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('students', 'academic_year_id')) {
            $incompleteStudents = DB::table('students')
                ->whereNull('academic_year_id')
                ->orWhereNull('classroom_id')
                ->orWhereNull('student_status_id')
                ->count();

            if ($incompleteStudents > 0) {
                throw new RuntimeException(
                    "Tidak dapat membuat enrollment: {$incompleteStudents} siswa belum memiliki kelas, tahun ajaran, atau status."
                );
            }
        }

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_status_id')->constrained()->restrictOnDelete();
            $table->date('enrolled_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
            $table->index(['classroom_id', 'academic_year_id']);
            $table->index(['academic_year_id', 'student_status_id']);
        });

        if (Schema::hasColumn('students', 'academic_year_id')) {
            $now = now();

            DB::table('students')
                ->orderBy('id')
                ->chunkById(500, function ($students) use ($now) {
                    DB::table('student_enrollments')->insert(
                        $students->map(fn ($student) => [
                            'student_id' => $student->id,
                            'academic_year_id' => $student->academic_year_id,
                            'classroom_id' => $student->classroom_id,
                            'student_status_id' => $student->student_status_id,
                            'enrolled_at' => null,
                            'ended_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->all()
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};

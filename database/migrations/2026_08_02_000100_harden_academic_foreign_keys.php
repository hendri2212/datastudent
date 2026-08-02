<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('majors', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['major_id']);
            $table->foreign('major_id')->references('id')->on('majors')->restrictOnDelete();
            $table->unique(['major_id', 'level', 'rombel']);
        });

        Schema::table('students', function (Blueprint $table) {
            foreach (['school_id', 'academic_year_id', 'student_status_id', 'religion_id', 'gender_id'] as $column) {
                $table->dropForeign([$column]);
                $table->foreign($column)->references('id')->on(match ($column) {
                    'school_id' => 'schools',
                    'academic_year_id' => 'academic_years',
                    'student_status_id' => 'student_statuses',
                    'religion_id' => 'religions',
                    'gender_id' => 'genders',
                })->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['school_id', 'academic_year_id', 'student_status_id', 'religion_id', 'gender_id'] as $column) {
                $table->dropForeign([$column]);
                $table->foreign($column)->references('id')->on(match ($column) {
                    'school_id' => 'schools',
                    'academic_year_id' => 'academic_years',
                    'student_status_id' => 'student_statuses',
                    'religion_id' => 'religions',
                    'gender_id' => 'genders',
                })->cascadeOnDelete();
            }
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropUnique(['major_id', 'level', 'rombel']);
            $table->dropForeign(['major_id']);
            $table->foreign('major_id')->references('id')->on('majors')->cascadeOnDelete();
        });

        Schema::table('majors', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });
    }
};

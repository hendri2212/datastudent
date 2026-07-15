<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Student Education History
     */
    public function up(): void
    {
        Schema::create('student_education_history', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Student
            |--------------------------------------------------------------------------
            */

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Education Level
            |--------------------------------------------------------------------------
            */

            $table->foreignId('education_level_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | School
            |--------------------------------------------------------------------------
            */

            $table->string('school_name');

            $table->string('npsn')->nullable();

            $table->text('address')->nullable();

            $table->year('entry_year')->nullable();

            $table->year('graduation_year')->nullable();

            $table->decimal('final_score',5,2)->nullable();

            $table->boolean('is_graduated')
                ->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            $table->index('student_id');

            $table->index('education_level_id');

            $table->unique([
                'student_id',
                'education_level_id'
            ]);
        });
    }

    /**
     * Reverse
     */
    public function down(): void
    {
        Schema::dropIfExists('student_education_history');
    }
};
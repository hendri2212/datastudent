<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Student Family
     */
    public function up(): void
    {
        Schema::create('student_family', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Student
            |--------------------------------------------------------------------------
            */

            $table->foreignId('student_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Father
            |--------------------------------------------------------------------------
            */

            $table->string('father_name')->nullable();

            $table->foreignId('father_occupation_id')
                ->nullable()
                ->constrained('occupations')
                ->nullOnDelete();

            $table->foreignId('father_income_category_id')
                ->nullable()
                ->constrained('income_categories')
                ->nullOnDelete();

            $table->string('father_phone',25)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Mother
            |--------------------------------------------------------------------------
            */

            $table->string('mother_name')->nullable();

            $table->foreignId('mother_occupation_id')
                ->nullable()
                ->constrained('occupations')
                ->nullOnDelete();

            $table->foreignId('mother_income_category_id')
                ->nullable()
                ->constrained('income_categories')
                ->nullOnDelete();

            $table->string('mother_phone',25)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Guardian
            |--------------------------------------------------------------------------
            */

            $table->string('guardian_name')->nullable();

            $table->foreignId('guardian_occupation_id')
                ->nullable()
                ->constrained('occupations')
                ->nullOnDelete();

            $table->foreignId('guardian_income_category_id')
                ->nullable()
                ->constrained('income_categories')
                ->nullOnDelete();

            $table->string('guardian_phone',25)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Emergency Contact
            |--------------------------------------------------------------------------
            */

            $table->string('emergency_contact_name')->nullable();

            $table->string('emergency_contact_phone',25)->nullable();

            $table->foreignId('relationship_type_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse
     */
    public function down(): void
    {
        Schema::dropIfExists('student_family');
    }
};
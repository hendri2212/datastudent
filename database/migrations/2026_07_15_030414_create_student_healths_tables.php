<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Student Healths
     */
    public function up(): void
    {
        Schema::create('student_healths', function (Blueprint $table) {

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
            | Physical
            |--------------------------------------------------------------------------
            */

            $table->decimal('height', 5, 2)->nullable()->comment('cm');

            $table->decimal('weight', 5, 2)->nullable()->comment('kg');

            $table->foreignId('blood_type_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Medical
            |--------------------------------------------------------------------------
            */

            $table->text('allergies')->nullable();

            $table->text('medical_history')->nullable();

            $table->text('disabilities')->nullable();

            $table->text('medications')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Emergency
            |--------------------------------------------------------------------------
            */

            $table->string('hospital')->nullable();

            $table->string('doctor')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            $table->index('blood_type_id');
        });
    }

    /**
     * Reverse
     */
    public function down(): void
    {
        Schema::dropIfExists('student_healths');
    }
};
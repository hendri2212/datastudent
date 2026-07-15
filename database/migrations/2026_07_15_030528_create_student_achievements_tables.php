<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_achievements', function (Blueprint $table) {

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
            | Achievement
            |--------------------------------------------------------------------------
            */

            $table->string('title');

            $table->string('organizer')->nullable();

            $table->string('level')->nullable(); // Sekolah, Kabupaten, Provinsi, Nasional, Internasional

            $table->string('category')->nullable(); // Akademik / Non Akademik

            $table->integer('rank')->nullable();

            $table->date('achievement_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Certificate
            |--------------------------------------------------------------------------
            */

            $table->string('certificate')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Description
            |--------------------------------------------------------------------------
            */

            $table->text('description')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_achievements');
    }
};
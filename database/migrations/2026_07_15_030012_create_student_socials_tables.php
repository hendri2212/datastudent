<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Student Socials
     */
    public function up(): void
    {
        Schema::create('student_socials', function (Blueprint $table) {

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
            | Social Platform
            |--------------------------------------------------------------------------
            */

            $table->foreignId('social_platform_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Account
            |--------------------------------------------------------------------------
            */

            $table->string('username')->nullable();

            $table->string('url')->nullable();

            $table->boolean('is_public')
                ->default(true);

            $table->boolean('is_primary')
                ->default(false);

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            $table->unique([
                'student_id',
                'social_platform_id'
            ]);

            $table->index([
                'student_id'
            ]);

            $table->index([
                'social_platform_id'
            ]);
        });
    }

    /**
     * Reverse
     */
    public function down(): void
    {
        Schema::dropIfExists('student_socials');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Students
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | User Login
            |--------------------------------------------------------------------------
            */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Master Data
            |--------------------------------------------------------------------------
            */
            $table->foreignId('religion_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('gender_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('citizenship_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            $table->string('nis', 30)->unique();

            $table->string('nisn', 10)->unique();

            $table->string('full_name');

            $table->string('nickname')->nullable();

            $table->string('birth_place');

            $table->date('birth_date');

            /*
            |--------------------------------------------------------------------------
            | Contact
            |--------------------------------------------------------------------------
            */

            $table->string('phone', 25)->nullable();

            $table->string('email')->nullable();

            $table->text('address')->nullable();

            $table->string('postal_code', 10)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Photo
            |--------------------------------------------------------------------------
            */

            $table->string('photo')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Lock Data
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_locked')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Verification
            |--------------------------------------------------------------------------
            */

            $table->timestamp('verified_at')
                ->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
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
        Schema::dropIfExists('students');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Schools
        |--------------------------------------------------------------------------
        */
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 20)->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Academic Years
        |--------------------------------------------------------------------------
        */
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Majors
        |--------------------------------------------------------------------------
        */
        Schema::create('majors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code',20);
            $table->string('name');
            $table->timestamps();

            $table->unique(['school_id','code']);
        });

        /*
        |--------------------------------------------------------------------------
        | Classrooms
        |--------------------------------------------------------------------------
        */
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('major_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->enum('level',['X','XI','XII','XIII']);
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Religions
        |--------------------------------------------------------------------------
        */
        Schema::create('religions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        /*
        |--------------------------------------------------------------------------
        | Genders
        |--------------------------------------------------------------------------
        */
        Schema::create('genders', function (Blueprint $table) {
            $table->id();
            $table->string('code',2)->unique();
            $table->string('name');
        });

        /*
        |--------------------------------------------------------------------------
        | Blood Types
        |--------------------------------------------------------------------------
        */
        Schema::create('blood_types', function (Blueprint $table) {
            $table->id();
            $table->string('name',3)->unique();
        });

        /*
        |--------------------------------------------------------------------------
        | Student Statuses
        |--------------------------------------------------------------------------
        */
        Schema::create('student_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        /*
        |--------------------------------------------------------------------------
        | Citizenships
        |--------------------------------------------------------------------------
        */
        Schema::create('citizenships', function (Blueprint $table) {
            $table->id();
            $table->string('code',10)->unique();
            $table->string('name');
        });

        /*
        |--------------------------------------------------------------------------
        | Social Platforms
        |--------------------------------------------------------------------------
        */
        Schema::create('social_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon')->nullable();
            $table->string('base_url')->nullable();
        });

        /*
        |--------------------------------------------------------------------------
        | Document Types
        |--------------------------------------------------------------------------
        */
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('required')->default(false);
        });

        /*
        |--------------------------------------------------------------------------
        | Education Levels
        |--------------------------------------------------------------------------
        */
        Schema::create('education_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('sort_order');
        });

        /*
        |--------------------------------------------------------------------------
        | Occupations
        |--------------------------------------------------------------------------
        */
        Schema::create('occupations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        /*
        |--------------------------------------------------------------------------
        | Income Categories
        |--------------------------------------------------------------------------
        */
        Schema::create('income_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->bigInteger('minimum')->nullable();
            $table->bigInteger('maximum')->nullable();
        });

        /*
        |--------------------------------------------------------------------------
        | Relationship Types
        |--------------------------------------------------------------------------
        */
        Schema::create('relationship_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_types');
        Schema::dropIfExists('income_categories');
        Schema::dropIfExists('occupations');
        Schema::dropIfExists('education_levels');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('social_platforms');
        Schema::dropIfExists('citizenships');
        Schema::dropIfExists('student_statuses');
        Schema::dropIfExists('blood_types');
        Schema::dropIfExists('genders');
        Schema::dropIfExists('religions');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('majors');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('schools');
    }
};
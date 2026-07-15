<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_logs', function (Blueprint $table) {

            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('verified_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('action',[
                'verified',
                'locked',
                'unlocked',
                'rejected'
            ]);

            $table->text('notes')->nullable();

            $table->timestamp('verified_at');

            $table->timestamps();

            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_logs');
    }
};
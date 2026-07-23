<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\MajorController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('siswa', 'students/Index')->name('students.index');

    // Route Jurusan
    Route::get('jurusan', [MajorController::class, 'index'])->name('majors.index');
    Route::resource('majors', MajorController::class)->only(['store', 'update', 'destroy']);

    // Route Khusus Soft & Hard Delete Kelas
    Route::post('classrooms/{id}/restore', [ClassroomController::class, 'restore'])->name('classrooms.restore');
    Route::delete('classrooms/{id}/force-delete', [ClassroomController::class, 'forceDelete'])->name('classrooms.force-delete');

    // Route Resource Kelas (Otomatis menangani GET index, POST store, PUT update, DELETE destroy)
    Route::resource('classrooms', ClassroomController::class);
});

require __DIR__.'/settings.php';
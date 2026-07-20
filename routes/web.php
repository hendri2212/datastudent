<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('jurusan', 'majors/Index')->name('majors.index');
    Route::inertia('kelas', 'classrooms/Index')->name('classrooms.index');
    Route::inertia('siswa', 'students/Index')->name('students.index');
});

require __DIR__.'/settings.php';

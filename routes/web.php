<?php

use App\Http\Controllers\AcademicYearController;
// Controller Utama & Siswa
use App\Http\Controllers\BloodTypeController;
use App\Http\Controllers\CitizenshipController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\EducationLevelController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\IncomeCategoryController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\OccupationController;
// Controller Master Data & Akademik
use App\Http\Controllers\RelationshipTypeController;
use App\Http\Controllers\ReligionController;
use App\Http\Controllers\SocialPlatformController;
use App\Http\Controllers\StudentAchievementController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDocumentController;
use App\Http\Controllers\StudentEducationHistoryController;
use App\Http\Controllers\StudentFamilyController;
use App\Http\Controllers\StudentHealthController;
use App\Http\Controllers\StudentSocialController;
use App\Http\Controllers\StudentStatusController;
use App\Http\Controllers\StudentVerificationController;
use Illuminate\Support\Facades\Route;

// 1. ROUTE HALAMAN UTAMA (LANDING PAGE)
Route::inertia('/', 'Welcome')->name('home');

// 2. ROUTE APLIKASI UTAMA (MEMBUTUHKAN AUTHENTICATION)
Route::middleware(['auth', 'verified'])->group(function () {

    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // ==========================================
    // ROUTE SISWA & SUB-RELASI
    // ==========================================
    Route::middleware('can:manage-students')->group(function () {
        // Custom Actions untuk Soft Deletes Siswa
        Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->middleware('can:manage-students')->name('students.restore');
        Route::delete('/students/{id}/force-delete', [StudentController::class, 'forceDelete'])->middleware('can:force-delete')->name('students.force-delete');

        // Resource Route Siswa (index, create, store, show, edit, update, destroy)
        Route::resource('students', StudentController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('can:manage-students');
        Route::get('/students/{student}/detail', [StudentController::class, 'detail'])->name('students.detail');

        // Verifikasi Siswa
        Route::post('/students/{student}/verify', [StudentVerificationController::class, 'verify'])->middleware('can:verify-students')->name('students.verify');
        Route::post('/students/{student}/unverify', [StudentVerificationController::class, 'unverify'])->middleware('can:verify-students')->name('students.unverify');

        // Data Keluarga Siswa
        Route::get('/students/{student}/family', [StudentFamilyController::class, 'edit'])->name('students.family.edit');
        Route::post('/students/{student}/family', [StudentFamilyController::class, 'update'])->name('students.family.update');

        // Data Riwayat Pendidikan Siswa (Student Education History)
        Route::post('/students/{student}/education-histories', [StudentEducationHistoryController::class, 'store'])->name('students.education-histories.store');
        Route::put('/education-histories/{studentEducationHistory}', [StudentEducationHistoryController::class, 'update'])->name('education-histories.update');
        Route::delete('/education-histories/{studentEducationHistory}', [StudentEducationHistoryController::class, 'destroy'])->name('education-histories.destroy');

        // Data Media Sosial Siswa
        Route::post('/students/{student}/socials', [StudentSocialController::class, 'store'])->name('students.socials.store');
        Route::put('/student-socials/{studentSocial}', [StudentSocialController::class, 'update'])->name('student-socials.update');
        Route::delete('/student-socials/{studentSocial}', [StudentSocialController::class, 'destroy'])->name('student-socials.destroy');

        // Dokumen Siswa
        Route::get('/students/{student}/photo', [StudentController::class, 'photo'])->name('students.photo');
        Route::get('/students/{student}/documents/{document}/preview', [StudentDocumentController::class, 'preview'])->name('students.documents.preview');
        Route::get('/students/{student}/documents/{document}/download', [StudentDocumentController::class, 'download'])->name('students.documents.download');
        Route::post('/students/{student}/documents/{document}/verify', [StudentDocumentController::class, 'verify'])->middleware('can:verify-students')->name('students.documents.verify');
        Route::delete('/students/{student}/documents/{document}', [StudentDocumentController::class, 'destroy'])->name('students.documents.destroy');
        Route::post('/students/{student}/documents', [StudentDocumentController::class, 'store']);

        // Data Kesehatan Siswa
        Route::post('/students/{student}/health', [StudentHealthController::class, 'storeOrUpdate'])->name('students.health.store');

        // Data Prestasi Siswa
        Route::post('/students/{student}/achievements', [StudentAchievementController::class, 'store'])->name('students.achievements.store');
        Route::put('/achievements/{achievement}', [StudentAchievementController::class, 'update'])->name('achievements.update');
        Route::delete('/achievements/{achievement}', [StudentAchievementController::class, 'destroy'])->name('achievements.destroy');

    });

    // ==========================================
    // ROUTE AKADEMIK (KELAS & JURUSAN)
    // ==========================================
    Route::post('/classrooms/{id}/restore', [ClassroomController::class, 'restore'])->middleware('can:manage-academics')->name('classrooms.restore');
    Route::delete('/classrooms/{id}/force-delete', [ClassroomController::class, 'forceDelete'])->middleware('can:force-delete')->name('classrooms.force-delete');
    Route::resource('classrooms', ClassroomController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('can:manage-academics');

    Route::resource('majors', MajorController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('can:manage-academics');

    // ==========================================
    // ROUTE MASTER DATA
    // ==========================================
    Route::prefix('master')->name('master.')->middleware('can:manage-master-data')->group(function () {
        Route::resource('education-levels', EducationLevelController::class)->except(['create', 'edit', 'show']);
        Route::resource('social-platforms', SocialPlatformController::class)->except(['create', 'edit', 'show']);
        Route::resource('academic-years', AcademicYearController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('genders', GenderController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('religions', ReligionController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('income-categories', IncomeCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('occupations', OccupationController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('relationship-types', RelationshipTypeController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    // ==========================================
    // API / JSON RESOURCE ROUTES
    // ==========================================
    Route::apiResource('blood-types', BloodTypeController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('can:manage-master-data');
    Route::apiResource('citizenships', CitizenshipController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('can:manage-master-data');
    Route::apiResource('student-statuses', StudentStatusController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('can:manage-master-data');

    // ==========================================
    // ROUTE SETTINGS (PROFILE, SECURITY, APPEARANCE)
    // ==========================================
    require __DIR__.'/settings.php';
});

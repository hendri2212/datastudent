<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Gender;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentDocument;
use App\Models\StudentStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BackendRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_creation_uses_enrollment_as_the_only_academic_source(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $classroom = Classroom::with('major')->firstOrFail();

        $response = $this->actingAs($operator)->post(route('students.store'), [
            'school_id' => $classroom->major->school_id,
            'major_id' => $classroom->major_id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => AcademicYear::firstOrFail()->id,
            'student_status_id' => StudentStatus::firstOrFail()->id,
            'gender_id' => Gender::firstOrFail()->id,
            'religion_id' => Religion::firstOrFail()->id,
            'nis' => '2026000001',
            'nisn' => '2000000001',
            'full_name' => 'Siswa Enrollment',
            'birth_place' => 'Denpasar',
            'birth_date' => '2010-01-01',
        ]);

        $response->assertRedirect(route('students.index'));
        $student = Student::where('nisn', '2000000001')->firstOrFail();

        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
        ]);
        $this->assertFalse(Schema::hasColumn('students', 'classroom_id'));
        $this->assertSame($classroom->id, $student->fresh()->classroom_id);
        $this->assertSame($classroom->major_id, $student->fresh()->major_id);
    }

    public function test_operator_cannot_verify_or_force_delete_students(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $student = Student::firstOrFail();

        $this->actingAs($operator)
            ->post(route('students.verify', $student))
            ->assertForbidden();

        $student->delete();

        $this->actingAs($operator)
            ->delete(route('students.force-delete', $student->id))
            ->assertForbidden();
    }

    public function test_admin_can_verify_students(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $student = Student::firstOrFail();

        $this->actingAs($admin)
            ->post(route('students.verify', $student))
            ->assertRedirect();

        $this->assertNotNull($student->fresh()->verified_at);
    }

    public function test_upload_restores_a_soft_deleted_document_without_leaving_the_old_file(): void
    {
        $storage = Storage::fake('public');
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $student = Student::firstOrFail();
        $document = StudentDocument::where('student_id', $student->id)->firstOrFail();
        $documentType = $document->documentType;
        $storage->put('student_documents/old.pdf', 'old');

        $document->update([
            'original_name' => 'old.pdf',
            'stored_name' => 'old.pdf',
            'file_path' => 'student_documents/old.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'file_size' => 3,
            'extension' => 'pdf',
        ]);
        $document->delete();

        $this->actingAs($operator)->post("/students/{$student->id}/documents", [
            'document_type_id' => $documentType->id,
            'file' => UploadedFile::fake()->create('new.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $restored = StudentDocument::whereKey($document->id)->firstOrFail();
        $this->assertSame('new.pdf', $restored->original_name);
        $storage->assertMissing('student_documents/old.pdf');
        $storage->assertExists($restored->file_path);
    }

    public function test_major_page_counts_students_through_active_enrollments(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($operator)
            ->get(route('majors.index'))
            ->assertOk();
    }

    public function test_academic_year_page_counts_students_through_enrollments(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
        ->get(route('master.academic-years.index'))
        ->assertOk();
    }

    public function test_student_academic_year_update_preserves_current_enrollment(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $student = Student::firstOrFail();
        $classroom = Classroom::with('major')->firstOrFail();
        $ay1 = AcademicYear::firstOrFail();
        $ay2 = AcademicYear::create([
            'name' => '2027/2028',
            'start_date' => '2027-07-01',
            'end_date' => '2028-06-30',
            'is_active' => false,
        ]);

        $payload = [
            'school_id' => $classroom->major->school_id,
            'major_id' => $classroom->major_id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $ay1->id,
            'student_status_id' => StudentStatus::firstOrFail()->id,
            'gender_id' => Gender::firstOrFail()->id,
            'religion_id' => Religion::firstOrFail()->id,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'full_name' => $student->full_name,
            'birth_place' => $student->birth_place,     
            'birth_date' => Carbon::parse($student->birth_date)->format('Y-m-d'),
        ];

        // 1. Update to AY 1
        $this->actingAs($operator)->post(route('students.update', $student), $payload)->assertRedirect();
        $this->assertSame($ay1->id, $student->fresh()->academic_year_id);
        $this->assertNotNull($student->fresh()->academic_year);

        // 2. Update to AY 2
        $payload['academic_year_id'] = $ay2->id;
        $this->actingAs($operator)->post(route('students.update', $student), $payload)->assertRedirect();
        $this->assertSame($ay2->id, $student->fresh()->academic_year_id);
        $this->assertNotNull($student->fresh()->academic_year);

        // 3. Update back to AY 1
        $payload['academic_year_id'] = $ay1->id;
        $this->actingAs($operator)->post(route('students.update', $student), $payload)->assertRedirect();
        $this->assertSame($ay1->id, $student->fresh()->academic_year_id);
        $this->assertNotNull($student->fresh()->academic_year);
    }

    public function test_student_role_cannot_update_verified_student_data_but_admin_can(): void
    {
        $studentUser = User::factory()->create(['role' => UserRole::Student]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $student = Student::firstOrFail();
        $student->update([
            'user_id' => $studentUser->id,
            'email' => $studentUser->email,
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);

        $classroom = Classroom::with('major')->firstOrFail();
        $payload = [
            'school_id' => $classroom->major->school_id,
            'major_id' => $classroom->major_id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => AcademicYear::firstOrFail()->id,
            'student_status_id' => StudentStatus::firstOrFail()->id,
            'gender_id' => Gender::firstOrFail()->id,
            'religion_id' => Religion::firstOrFail()->id,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'full_name' => 'Updated Name By Student',
            'birth_place' => $student->birth_place,
            'birth_date' => Carbon::parse($student->birth_date)->format('Y-m-d'),
        ];

        // Siswa tidak bisa update data ketika sudah diverifikasi -> 403 Forbidden
        $this->actingAs($studentUser)
            ->post(route('students.update', $student), $payload)
            ->assertForbidden();

        $this->assertNotSame('Updated Name By Student', $student->fresh()->full_name);

        // Admin tetap bisa update data meskipun sudah diverifikasi -> 302 Redirect
        $payload['full_name'] = 'Updated Name By Admin';
        $this->actingAs($admin)
            ->post(route('students.update', $student), $payload)
            ->assertRedirect();

        $this->assertSame('Updated Name By Admin', $student->fresh()->full_name);
    }

    public function test_student_role_can_update_unverified_student_data(): void
    {
        $studentUser = User::factory()->create(['role' => UserRole::Student]);
        $student = Student::firstOrFail();
        $student->update([
            'user_id' => $studentUser->id,
            'email' => $studentUser->email,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        $classroom = Classroom::with('major')->firstOrFail();
        $payload = [
            'school_id' => $classroom->major->school_id,
            'major_id' => $classroom->major_id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => AcademicYear::firstOrFail()->id,
            'student_status_id' => StudentStatus::firstOrFail()->id,
            'gender_id' => Gender::firstOrFail()->id,
            'religion_id' => Religion::firstOrFail()->id,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'full_name' => 'Student Name Before Verification',
            'birth_place' => $student->birth_place,
            'birth_date' => Carbon::parse($student->birth_date)->format('Y-m-d'),
        ];

        $this->actingAs($studentUser)
            ->post(route('students.update', $student), $payload)
            ->assertRedirect();

        $this->assertSame('Student Name Before Verification', $student->fresh()->full_name);
    }

    public function test_force_delete_trashed_student_removes_photo_certificate_and_document_files(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $publicStorage = Storage::fake('public');
        $privateStorage = Storage::fake('private');

        $student = Student::firstOrFail();
        $student->documents()->forceDelete();
        $student->achievements()->forceDelete();

        // 1. Setup Foto Siswa
        $photoPath = "student_photos/{$student->id}/photo.jpg";
        $publicStorage->put($photoPath, 'fake-photo-content');
        $student->update(['photo' => $photoPath]);

        // 2. Setup Sertifikat Prestasi
        $certPath = "certificates/{$student->id}/cert.pdf";
        $publicStorage->put($certPath, 'fake-certificate-content');
        $achievement = StudentAchievement::create([
            'student_id' => $student->id,
            'title' => 'Juara 1 Lomba Sains',
            'certificate' => $certPath,
        ]);

        // 3. Setup Dokumen Siswa
        $docPath = "student_documents/{$student->id}/ijazah.pdf";
        $publicStorage->put($docPath, 'fake-document-content');
        $document = StudentDocument::create([
            'student_id' => $student->id,
            'document_type_id' => 1,
            'original_name' => 'ijazah.pdf',
            'stored_name' => 'ijazah.pdf',
            'file_path' => $docPath,
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'extension' => 'pdf',
        ]);

        // Pastikan seluruh file ada sebelum dihapus
        $publicStorage->assertExists($photoPath);
        $publicStorage->assertExists($certPath);
        $publicStorage->assertExists($docPath);

        // Soft delete siswa terlebih dahulu (masuk tempat sampah)
        $student->delete();
        $this->assertTrue($student->fresh()->trashed());

        // Saat soft-delete, file masih tetap ada di storage
        $publicStorage->assertExists($photoPath);
        $publicStorage->assertExists($certPath);
        $publicStorage->assertExists($docPath);

        // Hapus permanen (force delete) dari tempat sampah
        $this->actingAs($admin)
            ->delete(route('students.force-delete', $student->id))
            ->assertRedirect();

        // Seluruh berkas fisik (Foto, Sertifikat, Dokumen) HARUS ikut terhapus
        $publicStorage->assertMissing($photoPath);
        $publicStorage->assertMissing($certPath);
        $publicStorage->assertMissing($docPath);

        // Record siswa sudah terhapus permanen dari database
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }
}

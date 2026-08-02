<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Gender;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('public');
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $student = Student::firstOrFail();
        $document = StudentDocument::where('student_id', $student->id)->firstOrFail();
        $documentType = $document->documentType;
        Storage::disk('public')->put('student_documents/old.pdf', 'old');

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
        Storage::disk('public')->assertMissing('student_documents/old.pdf');
        Storage::disk('public')->assertExists($restored->file_path);
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
}

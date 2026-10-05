<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StudentRequest;
use App\Models\AcademicYear;
use App\Models\BloodType;
use App\Models\Citizenship;
use App\Models\Classroom;
use App\Models\DocumentType;
use App\Models\EducationLevel;
use App\Models\Gender;
use App\Models\IncomeCategory;
use App\Models\Major;
use App\Models\Occupation;
use App\Models\RelationshipType;
use App\Models\Religion;
use App\Models\School;
use App\Models\SocialPlatform;
use App\Models\Student;
use App\Models\StudentStatus;
use App\Services\StudentDocumentService;
use App\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __construct(
        protected StudentService $studentService,
        protected StudentDocumentService $documentService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $student = null;

        if ($user) {
            // Cari data siswa berdasarkan user_id atau email user
            $student = Student::with($this->studentDetailRelations())
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                    if (! empty($user->email)) {
                        $query->orWhere('email', $user->email);
                    }
                })
                ->first();

            // Jika siswa ditemukan via email namun belum terhubung ke user_id, tautkan secara otomatis
            if ($student && ! $student->user_id) {
                $student->update(['user_id' => $user->id]);
            }
        }

        return Inertia::render('Welcome', array_merge(
            [
                'student' => $student,
                'hasRegistered' => (bool) $student,
            ], 
            $this->masterData() 
        ));
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && $this->isStudentRole($user)) {
            $existing = Student::where('user_id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    if (! empty($user->email)) {
                        $q->where('email', $user->email);
                    }
                })
                ->exists();

            if ($existing) {
                return redirect()->route('home')->with('error', 'Anda sudah mendaftarkan data siswa.');
            }
        }

        $data = $request->validated();

        $student = $this->studentService->create($data, $user?->id);

        $this->uploadFromForm($request, $student);

        if ($user && $this->isStudentRole($user)) {
            return redirect()->route('home')->with('success', 'Pendaftaran berhasil! Data kamu telah kami terima.');
        }

        return redirect()->back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    private function isStudentRole(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isStudent() || in_array($user->role->value, ['siswa', 'student', \App\Enums\UserRole::Student], true);
    }
    private function uploadFromForm(Request $request, Student $student): void
{
    // --- 1. HANDLE FOTO PROFIL ---
    // Cek 'photo_file' (dari Welcome.vue) ATAU 'photo' (form biasa)
    $photoFile = $request->file('photo_file') ?? $request->file('photo');

    if ($photoFile instanceof UploadedFile) {
        $path = $photoFile->store("students/{$student->id}/photo", 'public');
        $student->update(['photo' => $path]);
    }

    // --- 2. HANDLE DOKUMEN TUNGGAL (dari Welcome.vue) ---
    if ($request->hasFile('new_document_file') && $request->filled('document_type_id')) {
        $docFile = $request->file('new_document_file');
        
        if ($docFile instanceof UploadedFile) {
            $this->documentService->upload(
                student: $student,
                documentTypeId: (int) $request->input('document_type_id'),
                file: $docFile,
                notes: is_string($request->input('new_document_notes'))
                    ? $request->input('new_document_notes')
                    : (is_string($request->input('new_document_name')) ? $request->input('new_document_name') : null),
                uploadedBy: $request->user()?->id,
                disk: 'public'
            );
        }
    }

    // --- 3. HANDLE DOKUMEN ARRAY / BATCH (jika ada form lain yang memakai array) ---
    if ($request->hasFile('documents')) {
        /** @var array<mixed, mixed> $documents */
        $documents = $request->file('documents');

        foreach ($documents as $key => $doc) {
            if (is_array($doc) && isset($doc['file'], $doc['document_type_id']) && $doc['file'] instanceof UploadedFile) {
                $this->documentService->upload(
                    student: $student,
                    documentTypeId: (int) $doc['document_type_id'],
                    file: $doc['file'],
                    notes: is_string($doc['notes'] ?? null) ? $doc['notes'] : null,
                    uploadedBy: $request->user()?->id,
                    disk: 'public'
                );
            } elseif ($doc instanceof UploadedFile) {
                $this->documentService->upload(
                    student: $student,
                    documentTypeId: (int) $key,
                    file: $doc,
                    notes: null,
                    uploadedBy: $request->user()?->id,
                    disk: 'public'
                );
            }
        }
    }
}

    /**
     * @return array{
     *     schools: \Illuminate\Database\Eloquent\Collection<int, School>,
     *     majors: \Illuminate\Database\Eloquent\Collection<int, Major>,
     *     classrooms: \Illuminate\Database\Eloquent\Collection<int, Classroom>,
     *     academicYears: \Illuminate\Database\Eloquent\Collection<int, AcademicYear>,
     *     genders: \Illuminate\Database\Eloquent\Collection<int, Gender>,
     *     religions: \Illuminate\Database\Eloquent\Collection<int, Religion>,
     *     studentStatuses: \Illuminate\Database\Eloquent\Collection<int, StudentStatus>,
     *     bloodTypes: \Illuminate\Database\Eloquent\Collection<int, BloodType>,
     *     citizenships: \Illuminate\Database\Eloquent\Collection<int, Citizenship>,
     *     occupations: \Illuminate\Database\Eloquent\Collection<int, Occupation>,
     *     incomeCategories: \Illuminate\Database\Eloquent\Collection<int, IncomeCategory>,
     *     relationshipTypes: \Illuminate\Database\Eloquent\Collection<int, RelationshipType>,
     *     educationLevels: \Illuminate\Database\Eloquent\Collection<int, EducationLevel>,
     *     socialPlatforms: \Illuminate\Database\Eloquent\Collection<int, SocialPlatform>,
     *     documentTypes: \Illuminate\Database\Eloquent\Collection<int, DocumentType>
     * }
     */
    private function masterData(): array
    {
        return [
            'schools'           => School::select('id', 'name')->get(),
            'majors'            => Major::select('id', 'school_id', 'code', 'name')->get(),
            'classrooms'        => Classroom::select('id', 'major_id', 'name')->get(),
            'academicYears'     => AcademicYear::select('id', 'name', 'is_active')->get(),
            'genders'           => Gender::select('id', 'code', 'name')->get(),
            'religions'         => Religion::select('id', 'name')->get(),
            'studentStatuses'   => StudentStatus::select('id', 'name')->get(),
            'bloodTypes'        => BloodType::select('id', 'name')->get(),
            'citizenships'      => Citizenship::select('id', 'name')->get(),
            'occupations'       => Occupation::select('id', 'name')->get(),
            'incomeCategories'  => IncomeCategory::select('id', 'name')->get(),
            'relationshipTypes' => RelationshipType::select('id', 'name')->get(),
            'educationLevels'   => EducationLevel::select('id', 'name')->get(),
            'socialPlatforms'   => SocialPlatform::select('id', 'name')->get(),
            'documentTypes'     => DocumentType::select('id', 'name')->get(),
        ];
    }

    /** @return list<string> */
    private function studentDetailRelations(): array
    {
        return [
            'user:id,name,email,role',
            'citizenship:id,name',
            'gender:id,code,name',
            'religion:id,name',
            'verifier:id,name',
            'currentEnrollment.classroom.major.school',
            'currentEnrollment.academicYear:id,name,is_active,start_date,end_date',
            'currentEnrollment.status:id,name',
            'family.fatherOccupation:id,name',
            'family.fatherIncomeCategory:id,name',
            'family.motherOccupation:id,name',
            'family.motherIncomeCategory:id,name',
            'family.guardianOccupation:id,name',
            'family.guardianIncomeCategory:id,name',
            'family.relationshipType:id,name',
            'educationHistories.educationLevel:id,name',
            'health.bloodType:id,name',
            'achievements',
            'documents.documentType:id,name',
            'documents.verifier:id,name',
            'socials.socialPlatform:id,name,icon,base_url',
            'violations',
        ];
    }
}

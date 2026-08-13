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

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user && $this->isStudentRole($user)) {
            if (Student::where('user_id', $user->id)->exists()) {
                return redirect()->route('dashboard');
            }
        }

        return Inertia::render('Welcome', array_merge(
            [
                'hasRegistered' => false,
            ], 
            $this->masterData() 
        ));
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && $this->isStudentRole($user)) {
            if (Student::where('user_id', $user->id)->exists()) {
                return redirect()->route('dashboard')->with('error', 'Anda sudah mendaftarkan data siswa.');
            }
        }

        $data = $request->validated();

        $student = $this->studentService->create($data, $user?->id);

        $this->uploadFromForm($request, $student);

        if ($user && $this->isStudentRole($user)) {
            return redirect()->route('dashboard')->with('success', 'Pendaftaran berhasil! Data kamu telah kami terima.');
        }

        return redirect()->back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

   private function isStudentRole(?User $user): bool
    {
        return isset($user->role) && in_array($user->role, ['siswa', 'student']);
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
                notes: is_string($request->input('new_document_name')) ? $request->input('new_document_name') : null,
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
}
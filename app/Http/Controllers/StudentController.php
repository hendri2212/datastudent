<?php

namespace App\Http\Controllers;

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
use App\Models\StudentAchievement;
use App\Models\StudentStatus;
use App\Models\StudentViolation;
use App\Services\StudentDocumentService;
use App\Services\StudentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $students,
        private readonly StudentDocumentService $documents,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'citizenship_id' => ['nullable', 'integer', 'exists:citizenships,id'],
            'gender_id' => ['nullable', 'integer', 'exists:genders,id'],
            'religion_id' => ['nullable', 'integer', 'exists:religions,id'],
            'student_status_id' => ['nullable', 'integer', 'exists:student_statuses,id'],
            'blood_type_id' => ['nullable', 'integer', 'exists:blood_types,id'],
            'tab' => ['nullable', 'in:active,trashed'],
        ]);

        /** @var Builder<Student> $query */
        $query = Student::query()->with($this->studentRelations());

        ($filters['tab'] ?? 'active') === 'trashed'
            ? $query->onlyTrashed()
            : $query->withoutTrashed();

        $query
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "{$search}%")
                        ->orWhere('nis', 'like', "{$search}%")
                        ->orWhere('phone', 'like', "{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(
                collect(['classroom_id', 'major_id', 'academic_year_id', 'student_status_id'])->contains(fn ($key) => isset($filters[$key])),
                fn ($query) => $query->whereHas('enrollments', function ($enrollments) use ($filters) {
                    $enrollments
                        ->when($filters['classroom_id'] ?? null, fn ($q, $id) => $q->where('classroom_id', $id))
                        ->when($filters['major_id'] ?? null, fn ($q, $id) => $q->whereHas('classroom', fn ($classroom) => $classroom->where('major_id', $id)))
                        ->when($filters['academic_year_id'] ?? null, fn ($q, $id) => $q->where('academic_year_id', $id))
                        ->when($filters['student_status_id'] ?? null, fn ($q, $id) => $q->where('student_status_id', $id));
                }),
            )
            ->when($filters['citizenship_id'] ?? null, fn ($query, $id) => $query->where('citizenship_id', $id))
            ->when($filters['gender_id'] ?? null, fn ($query, $id) => $query->where('gender_id', $id))
            ->when($filters['religion_id'] ?? null, fn ($query, $id) => $query->where('religion_id', $id))
            ->when($filters['blood_type_id'] ?? null, fn ($query, $id) => $query->whereHas('health', fn ($health) => $health->where('blood_type_id', $id)));

        $statistics = $this->statistics(clone $query);
        $students = (clone $query)->latest('id')->paginate(10)->withQueryString();

        return Inertia::render('students/Index', [
            'students' => $students,
            'statistics' => $statistics,
            ...$this->masterData(),
            'filters' => [
                'search' => '',
                'classroom_id' => '',
                'major_id' => '',
                'academic_year_id' => '',
                'citizenship_id' => '',
                'gender_id' => '',
                'religion_id' => '',
                'student_status_id' => '',
                'blood_type_id' => '',
                'tab' => 'active',
                ...$filters,
            ],
        ]);
    }

    public function detail(Student $student): JsonResponse
    {
        return response()->json([
            'student' => $student->load($this->studentDetailRelations()),
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $student = $this->students->create($data, $request->user()?->id);
        $this->uploadFromForm($request, $student, $data);

        return redirect()->route('students.index')->with('success', 'Data siswa lengkap berhasil disimpan.');
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();
        $this->students->update($student, $data, $request->user()?->id);
        $this->uploadFromForm($request, $student, $data);

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return back()->with('success', 'Data siswa berhasil dipindahkan ke sampah.');
    }

    public function restore(int $id): RedirectResponse
    {
        Student::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Data siswa berhasil dipulihkan.');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $student = Student::onlyTrashed()->with(['documents' => fn ($query) => $query->withTrashed()])->findOrFail($id);

        foreach ($student->documents as $document) {
            if ($document->file_path) {
                Storage::disk($document->disk ?: 'private')->delete($document->file_path);
            }
        }

        $student->forceDelete();

        return back()->with('success', 'Data siswa berhasil dihapus permanen.');
    }

    /** @return list<string> */
    private function studentRelations(): array
    {
        return [
            'citizenship:id,name',
            'gender:id,code,name',
            'religion:id,name',
            'verifier:id,name',
            'currentEnrollment.classroom.major.school',
            'currentEnrollment.academicYear:id,name,is_active,start_date,end_date',
            'currentEnrollment.status:id,name',
        ];
    }

    /** @return list<string> */
    private function studentDetailRelations(): array
    {
        return [
            ...$this->studentRelations(),
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

    /**
     * @param  Builder<Student>  $query
     * @return array<string, mixed>
     */
    private function statistics(Builder $query): array
    {
        $studentIds = (clone $query)->reorder()->select('students.id');
        $genderCounts = (clone $query)
            ->reorder()
            ->selectRaw('gender_id, COUNT(*) as aggregate')
            ->groupBy('gender_id')
            ->pluck('aggregate', 'gender_id');

        return [
            'total' => (clone $query)->count(),
            'verified' => (clone $query)->whereNotNull('verified_at')->count(),
            'unverified' => (clone $query)->whereNull('verified_at')->count(),
            'achievements' => StudentAchievement::whereIn('student_id', clone $studentIds)->count(),
            'violation_points' => (int) StudentViolation::whereIn('student_id', clone $studentIds)->sum('point'),
            'genders' => Gender::whereIn('id', $genderCounts->keys())
                ->pluck('name', 'id')
                ->mapWithKeys(fn (string $name, int $id) => [$name => (int) $genderCounts[$id]])
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function masterData(): array
    {
        return [
            'schools' => School::select('id', 'name')->get(),
            'majors' => Major::select('id', 'school_id', 'code', 'name')->get(),
            'classrooms' => Classroom::select('id', 'major_id', 'name')->get(),
            'academicYears' => AcademicYear::select('id', 'name', 'is_active')->get(),
            'genders' => Gender::select('id', 'code', 'name')->get(),
            'religions' => Religion::select('id', 'name')->get(),
            'studentStatuses' => StudentStatus::select('id', 'name')->get(),
            'bloodTypes' => BloodType::select('id', 'name')->get(),
            'citizenships' => Citizenship::select('id', 'name')->get(),
            'occupations' => Occupation::select('id', 'name')->get(),
            'incomeCategories' => IncomeCategory::select('id', 'name')->get(),
            'relationshipTypes' => RelationshipType::select('id', 'name')->get(),
            'educationLevels' => EducationLevel::select('id', 'name')->get(),
            'socialPlatforms' => SocialPlatform::select('id', 'name')->get(),
            'documentTypes' => DocumentType::select('id', 'name')->get(),
        ];
    }

    /** @param array<string, mixed> $data */
    private function uploadFromForm(StudentRequest $request, Student $student, array $data): void
    {
        $file = $request->file('new_document_file');

        if ($file === null) {
            return;
        }

        $this->documents->upload(
            $student,
            (int) $data['document_type_id'],
            $file,
            $data['new_document_name'] ?? null,
            $request->user()?->id,
        );
    }
}

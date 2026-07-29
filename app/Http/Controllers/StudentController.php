<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    /**
     * Menampilkan daftar siswa & menyediakan seluruh data master untuk dialog form
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $classroomId = $request->input('classroom_id');
        $majorId = $request->input('major_id');
        $academicYearId = $request->input('academic_year_id');
        $citizenshipId = $request->input('citizenship_id');
        $genderId = $request->input('gender_id');
        $religionId = $request->input('religion_id');
        $studentStatusId = $request->input('student_status_id');
        $bloodTypeId = $request->input('blood_type_id');
        $tab = $request->input('tab', 'active');

        // Eager load seluruh relasi utama dan relasi pendukung
        $query = Student::query()
            ->with([
                'classroom',
                'major',
                'school',
                'citizenship',
                'gender',
                'religion',
                'academicYear',
                'status',
                'verifier',
                'family.fatherOccupation',
                'family.fatherIncomeCategory',
                'family.motherOccupation',
                'family.motherIncomeCategory',
                'family.guardianOccupation',
                'family.guardianIncomeCategory',
                'family.relationshipType',
                'educationHistories.educationLevel',
                'health.bloodType',
                'achievements',
                'documents.documentType',
                'documents.verifier',
                'socials.socialPlatform',
                'violations',
            ]);

        if ($tab === 'trashed') {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query->onlyTrashed();
        } else {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query->withoutTrashed();
        }

        $students = $query
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nisn', 'like', "%{$search}%")
                      ->orWhere('nis', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($classroomId, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($majorId, fn ($query) => $query->where('major_id', $majorId))
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($citizenshipId, fn ($query) => $query->where('citizenship_id', $citizenshipId))
            ->when($genderId, fn ($query) => $query->where('gender_id', $genderId))
            ->when($religionId, fn ($query) => $query->where('religion_id', $religionId))
            ->when($studentStatusId, fn ($query) => $query->where('student_status_id', $studentStatusId))
            ->when($bloodTypeId, fn ($query) => $query->where('blood_type_id', $bloodTypeId))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('students/Index', [
            'students' => $students,
            
            // --- DATA MASTER LENGKAP UNTUK FORM DROPDOWN ---
            'schools'           => School::select('id', 'name')->get(),
            'majors'            => Major::select('id', 'code', 'name')->get(),
            'classrooms'        => Classroom::select('id', 'name')->get(),
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

            'filters' => [
                'search'             => $search ?? '',
                'classroom_id'       => $classroomId ?? '',
                'major_id'           => $majorId ?? '',
                'academic_year_id'   => $academicYearId ?? '',
                'citizenship_id'     => $citizenshipId ?? '',
                'gender_id'          => $genderId ?? '',
                'religion_id'        => $religionId ?? '',
                'student_status_id'  => $studentStatusId ?? '',
                'blood_type_id'      => $bloodTypeId ?? '',
                'tab'                => $tab,
            ],
        ]);
    }

    /**
     * Menyimpan data siswa baru beserta data relasinya
     */
    public function store(Request $request): RedirectResponse
    {
        // Safe Default Values jika tidak dikirim dari form
        $defaultSchoolId = School::value('id');
        $defaultStatusId = StudentStatus::where('name', 'Aktif')->value('id') ?? StudentStatus::value('id');

        $validated = $request->validate([
            // Akademik & Utama
            'school_id'         => ['required', 'exists:schools,id'],
            'major_id'          => ['nullable', 'exists:majors,id'],
            'classroom_id'      => ['nullable', 'exists:classrooms,id'],
            'academic_year_id'  => ['required', 'exists:academic_years,id'],
            'gender_id'         => ['required', 'exists:genders,id'],
            'religion_id'       => ['required', 'exists:religions,id'],
            'student_status_id' => ['required', 'exists:student_statuses,id'],
            'citizenship_id'    => ['nullable', 'exists:citizenships,id'],
            'blood_type_id'     => ['nullable', 'exists:blood_types,id'],
            'nisn'              => ['required', 'string', 'max:20', 'unique:students,nisn'],
            'nis'               => ['required', 'string', 'max:30', 'unique:students,nis'],
            'full_name'         => ['required', 'string', 'max:255'],
            'nickname'          => ['nullable', 'string', 'max:100'],
            'birth_place'       => ['required', 'string', 'max:100'],
            'birth_date'        => ['required', 'date'],
            'phone'             => ['nullable', 'string', 'max:25'],
            'email'             => ['nullable', 'email', 'max:255'],
            'address'           => ['nullable', 'string'],
            'postal_code'       => ['nullable', 'string', 'max:10'],

            // Data Keluarga (Family)
            'family'                              => ['nullable', 'array'],
            'family.father_name'                  => ['nullable', 'string', 'max:255'],
            'family.father_occupation_id'         => ['nullable', 'exists:occupations,id'],
            'family.father_income_category_id'    => ['nullable', 'exists:income_categories,id'],
            'family.father_phone'                 => ['nullable', 'string', 'max:25'],
            'family.mother_name'                  => ['nullable', 'string', 'max:255'],
            'family.mother_occupation_id'         => ['nullable', 'exists:occupations,id'],
            'family.mother_income_category_id'    => ['nullable', 'exists:income_categories,id'],
            'family.mother_phone'                 => ['nullable', 'string', 'max:25'],
            'family.guardian_name'                => ['nullable', 'string', 'max:255'],
            'family.guardian_occupation_id'       => ['nullable', 'exists:occupations,id'],
            'family.guardian_income_category_id'  => ['nullable', 'exists:income_categories,id'],
            'family.guardian_phone'               => ['nullable', 'string', 'max:25'],
            'family.emergency_contact_name'       => ['nullable', 'string', 'max:255'],
            'family.emergency_contact_phone'      => ['nullable', 'string', 'max:25'],
            'family.relationship_type_id'         => ['nullable', 'exists:relationship_types,id'],
            'family.notes'                        => ['nullable', 'string'],

            // Riwayat Sekolah (Array)
            'education_histories'                      => ['nullable', 'array'],
            'education_histories.*.education_level_id' => ['required', 'exists:education_levels,id'],
            'education_histories.*.school_name'        => ['nullable', 'string', 'max:255'],
            'education_histories.*.npsn'               => ['nullable', 'string', 'max:30'],
            'education_histories.*.address'            => ['nullable', 'string'],
            'education_histories.*.entry_year'         => ['nullable', 'integer', 'digits:4'],
            'education_histories.*.graduation_year'    => ['nullable', 'integer', 'digits:4', 'gte:education_histories.*.entry_year'],
            'education_histories.*.final_score'        => ['nullable', 'numeric', 'between:0,100.00'],
            'education_histories.*.is_graduated'       => ['boolean'],
            'education_histories.*.notes'              => ['nullable', 'string'],

            // Kesehatan (Health)
            'health'                             => ['nullable', 'array'],
            'health.height'                      => ['nullable', 'numeric'],
            'health.weight'                      => ['nullable', 'numeric'],
            'health.blood_type_id'               => ['nullable', 'exists:blood_types,id'],
            'health.allergies'                   => ['nullable', 'string'],
            'health.medical_history'             => ['nullable', 'string'],
            'health.disabilities'                => ['nullable', 'string'],
            'health.medications'                 => ['nullable', 'string'],
            'health.hospital'                    => ['nullable', 'string', 'max:255'],
            'health.doctor'                      => ['nullable', 'string', 'max:255'],
            'health.notes'                       => ['nullable', 'string'],

            // Media Sosial (Array)
            'socials'                          => ['nullable', 'array'],
            'socials.*.social_platform_id'     => ['required_with:socials', 'exists:social_platforms,id', 'distinct'],
            'socials.*.username'               => ['nullable', 'string', 'max:100'],
            'socials.*.url'                    => ['nullable', 'string', 'max:255'],
            'socials.*.is_public'              => ['boolean'],
            'socials.*.is_primary'             => ['boolean'],

            // Prestasi (Array)
            'achievements'                       => ['nullable', 'array'],
            'achievements.*.title'               => ['nullable', 'string', 'max:255'],
            'achievements.*.organizer'           => ['nullable', 'string', 'max:255'],
            'achievements.*.level'               => ['nullable', 'string', 'max:100'],
            'achievements.*.category'            => ['nullable', 'string', 'max:100'],
            'achievements.*.rank'                => ['nullable', 'integer'],
            'achievements.*.achievement_date'    => ['nullable', 'date'],
            'achievements.*.description'         => ['nullable', 'string'],

            // Dokumen Upload
            'document_type_id'  => ['nullable', 'exists:document_types,id'],
            'new_document_name' => ['nullable', 'string', 'max:255'],
            'new_document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $validated['school_id'] = $validated['school_id'] ?? $defaultSchoolId;
        $validated['student_status_id'] = $validated['student_status_id'] ?? $defaultStatusId;

        DB::transaction(function () use ($request, $validated) {
            /** @var \App\Models\Student $student */
            $student = Student::create(Arr::except($validated, [
                'family',
                'education_histories',
                'health',
                'socials',
                'achievements',
                'new_document_file',
                'new_document_name',
            ]));

            if (!empty($validated['family'])) {
                $student->family()->create($validated['family']);
            }

            if (!empty($validated['education_histories'])) {
                foreach ($validated['education_histories'] as $history) {
                    if (!empty($history['education_level_id'])) {
                        $student->educationHistories()->create([
                            'education_level_id' => $history['education_level_id'],
                            'school_name'        => $history['school_name'] ?? null,
                            'npsn'               => $history['npsn'] ?? null,
                            'address'            => $history['address'] ?? null,
                            'entry_year'         => $history['entry_year'] ?? null,
                            'graduation_year'    => $history['graduation_year'] ?? null,
                            'final_score'        => $history['final_score'] ?? null,
                            'is_graduated'       => $history['is_graduated'] ?? true,
                            'notes'              => $history['notes'] ?? null,
                        ]);
                    }
                }
            }

            if (!empty($validated['health'])) {
                $student->health()->create($validated['health']);
            }

            if (!empty($validated['socials'])) {
                foreach ($validated['socials'] as $social) {
                    if (!empty($social['social_platform_id'])) {
                        $student->socials()->create([
                            'social_platform_id' => $social['social_platform_id'],
                            'username'           => $social['username'] ?? null,
                            'url'                => $social['url'] ?? null,
                            'is_public'          => $social['is_public'] ?? false,
                            'is_primary'         => $social['is_primary'] ?? false,
                        ]);
                    }
                }
            }

            if (!empty($validated['achievements'])) {
                foreach ($validated['achievements'] as $ach) {
                    if (!empty($ach['title'])) {
                        $student->achievements()->create([
                            'title'            => $ach['title'],
                            'organizer'        => $ach['organizer'] ?? null,
                            'level'            => $ach['level'] ?? null,
                            'category'         => $ach['category'] ?? null,
                            'rank'             => $ach['rank'] ?? null,
                            'achievement_date' => $ach['achievement_date'] ?? null,
                            'description'      => $ach['description'] ?? null,
                        ]);
                    }
                }
            }

            if (!empty($validated['violations'])) {
                foreach ($validated['violations'] as $violation) {
                    if (!empty($violation['title'])) {
                        $student->violations()->create([
                            'title'          => $violation['title'],
                            'point'          => $violation['point'] ?? 0,
                            'violation_date' => $violation['violation_date'] ?? null,
                            'description'    => $violation['description'] ?? null,
                            'reported_by'    => Auth::id(),
                        ]);
                    }
                }
            }

            if ($request->hasFile('new_document_file')) {
                $file = $request->file('new_document_file');
                $filePath = $file->store('student_documents', 'public');

                $defaultDocTypeId = class_exists(DocumentType::class)
                    ? (DocumentType::value('id') ?? 1)
                    : 1;

                $documentTypeId = $request->input('document_type_id', $defaultDocTypeId);

                $existingDocument = $student->documents()
                    ->where('document_type_id', $documentTypeId)
                    ->first();

                if ($existingDocument && $existingDocument->file_path) {
                    Storage::disk($existingDocument->disk ?? 'public')
                        ->delete($existingDocument->file_path);
                }

                $student->documents()->updateOrCreate(
                    [
                        'student_id'       => $student->id,
                        'document_type_id' => $documentTypeId,
                    ],
                    [
                        'original_name' => $file->getClientOriginalName(),
                        'stored_name'   => basename($filePath),
                        'file_path'     => $filePath,
                        'extension'     => strtolower($file->getClientOriginalExtension()),
                        'disk'          => 'public',
                        'file_size'     => $file->getSize(),
                        'mime_type'     => $file->getClientMimeType(),
                        'notes'         => $validated['new_document_name'] ?? null,
                        'uploaded_by'   => Auth::id(),
                    ]
                );
            }
        });

        return redirect()->route('students.index')->with('success', 'Data siswa lengkap berhasil disimpan.');
    }

    /**
     * Memperbarui data siswa beserta relasinya
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'school_id'         => ['required', 'exists:schools,id'],
            'major_id'          => ['nullable', 'exists:majors,id'],
            'classroom_id'      => ['nullable', 'exists:classrooms,id'],
            'academic_year_id'  => ['required', 'exists:academic_years,id'],
            'gender_id'         => ['required', 'exists:genders,id'],
            'religion_id'       => ['nullable', 'exists:religions,id'],
            'citizenship_id'    => ['nullable', 'exists:citizenships,id'],
            'student_status_id' => ['required', 'exists:student_statuses,id'],
            'nisn'              => ['required', 'string', 'max:20', 'unique:students,nisn,' . $student->id],
            'nis'               => ['nullable', 'string', 'max:30', 'unique:students,nis,' . $student->id],
            'full_name'         => ['required', 'string', 'max:255'],
            'nickname'          => ['nullable', 'string', 'max:100'],
            'birth_place'       => ['required', 'string', 'max:100'],
            'birth_date'        => ['required', 'date'],
            'phone'             => ['nullable', 'string', 'max:25'],
            'email'             => ['nullable', 'email', 'max:255'],
            'address'           => ['nullable', 'string'],
            'postal_code'       => ['nullable', 'string', 'max:10'],

            'family'                              => ['nullable', 'array'],
            'family.father_name'                  => ['nullable', 'string', 'max:255'],
            'family.father_occupation_id'         => ['nullable', 'exists:occupations,id'],
            'family.father_income_category_id'    => ['nullable', 'exists:income_categories,id'],
            'family.father_phone'                 => ['nullable', 'string', 'max:25'],
            'family.mother_name'                  => ['nullable', 'string', 'max:255'],
            'family.mother_occupation_id'         => ['nullable', 'exists:occupations,id'],
            'family.mother_income_category_id'    => ['nullable', 'exists:income_categories,id'],
            'family.mother_phone'                 => ['nullable', 'string', 'max:25'],
            'family.guardian_name'                => ['nullable', 'string', 'max:255'],
            'family.guardian_occupation_id'       => ['nullable', 'exists:occupations,id'],
            'family.guardian_income_category_id'  => ['nullable', 'exists:income_categories,id'],
            'family.guardian_phone'               => ['nullable', 'string', 'max:25'],
            'family.emergency_contact_name'       => ['nullable', 'string', 'max:255'],
            'family.emergency_contact_phone'      => ['nullable', 'string', 'max:25'],
            'family.relationship_type_id'         => ['nullable', 'exists:relationship_types,id'],
            'family.notes'                        => ['nullable', 'string'],

            'education_histories'                      => ['nullable', 'array'],
            'education_histories.*.id'                 => ['nullable', 'exists:student_education_history,id'],
            'education_histories.*.education_level_id' => ['required_with:education_histories', 'exists:education_levels,id'],
            'education_histories.*.school_name'        => ['nullable', 'string', 'max:255'],
            'education_histories.*.npsn'               => ['nullable', 'string', 'max:30'],
            'education_histories.*.address'            => ['nullable', 'string'],
            'education_histories.*.entry_year'         => ['nullable', 'integer', 'digits:4'],
            'education_histories.*.graduation_year'    => ['nullable', 'integer', 'digits:4', 'gte:education_histories.*.entry_year'],
            'education_histories.*.final_score'        => ['nullable', 'numeric', 'between:0,100.00'],
            'education_histories.*.is_graduated'       => ['boolean'],
            'education_histories.*.notes'              => ['nullable', 'string'],

            'health'                            => ['nullable', 'array'],
            'health.height'                     => ['nullable', 'numeric'],
            'health.weight'                     => ['nullable', 'numeric'],
            'health.blood_type_id'              => ['nullable', 'exists:blood_types,id'],
            'health.allergies'                  => ['nullable', 'string'],
            'health.medical_history'            => ['nullable', 'string'],
            'health.disabilities'               => ['nullable', 'string'],
            'health.medications'                => ['nullable', 'string'],
            'health.hospital'                   => ['nullable', 'string', 'max:255'],
            'health.doctor'                     => ['nullable', 'string', 'max:255'],
            'health.notes'                      => ['nullable', 'string'],

            'socials'                           => ['nullable', 'array'],
            'socials.*.id'                      => ['nullable', 'exists:student_socials,id'],
            'socials.*.social_platform_id'      => ['required_with:socials', 'exists:social_platforms,id','distinct'],
            'socials.*.username'                => ['nullable', 'string', 'max:100'],
            'socials.*.url'                     => ['nullable', 'string', 'max:255'],
            'socials.*.is_public'               => ['boolean'],
            'socials.*.is_primary'              => ['boolean'],

            'achievements'                      => ['nullable', 'array'],
            'achievements.*.id'                 => ['nullable', 'exists:student_achievements,id'],
            'achievements.*.title'              => ['nullable', 'string', 'max:255'],
            'achievements.*.organizer'          => ['nullable', 'string', 'max:255'],
            'achievements.*.level'              => ['nullable', 'string', 'max:100'],
            'achievements.*.category'           => ['nullable', 'string', 'max:100'],
            'achievements.*.rank'               => ['nullable', 'integer'],
            'achievements.*.achievement_date'   => ['nullable', 'date'],
            'achievements.*.description'        => ['nullable', 'string'],

            'violations'                        => ['nullable', 'array'],
            'violations.*.id'                   => ['nullable', 'exists:student_violations,id'],
            'violations.*.title'                => ['required_with:violations', 'string', 'max:255'],
            'violations.*.point'                => ['nullable', 'integer'],
            'violations.*.violation_date'       => ['nullable', 'date'],
            'violations.*.description'          => ['nullable', 'string'],

            'document_type_id'  => ['nullable', 'exists:document_types,id'],
            'new_document_name' => ['nullable', 'string', 'max:255'],
            'new_document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        DB::transaction(function () use ($request, $student, $validated) {
            $student->update(Arr::except($validated, [
                'family',
                'education_histories',
                'health',
                'socials',
                'achievements',
                'violations',
                'new_document_file',
                'new_document_name',
            ]));

            if (!empty($validated['family'])) {
                $student->family()->updateOrCreate(
                    ['student_id' => $student->id],
                    $validated['family']
                );
            }

            if (array_key_exists('education_histories', $validated)) {
                if (!empty($validated['education_histories'])) {
                    $keptEducationIds = [];
                    foreach ($validated['education_histories'] as $history) {
                        if (!empty($history['education_level_id'])) {
                            if (!empty($history['id'])) {
                                $record = $student->educationHistories()->find($history['id']);
                                if ($record) {
                                    $record->update([
                                        'education_level_id' => $history['education_level_id'],
                                        'school_name'        => $history['school_name'] ?? null,
                                        'npsn'               => $history['npsn'] ?? null,
                                        'address'            => $history['address'] ?? null,
                                        'entry_year'         => $history['entry_year'] ?? null,
                                        'graduation_year'    => $history['graduation_year'] ?? null,
                                        'final_score'        => $history['final_score'] ?? null,
                                        'is_graduated'       => $history['is_graduated'] ?? true,
                                        'notes'              => $history['notes'] ?? null,
                                    ]);
                                }
                            } else {
                                $record = $student->educationHistories()->updateOrCreate(
                                    [
                                        'student_id'       => $student->id,
                                        'education_level_id' => $history['education_level_id'],
                                    ],
                                    [
                                        'school_name'        => $history['school_name'] ?? null,
                                        'npsn'               => $history['npsn'] ?? null,
                                        'address'            => $history['address'] ?? null,
                                        'entry_year'         => $history['entry_year'] ?? null,
                                        'graduation_year'    => $history['graduation_year'] ?? null,
                                        'final_score'        => $history['final_score'] ?? null,
                                        'is_graduated'       => $history['is_graduated'] ?? true,
                                        'notes'              => $history['notes'] ?? null,
                                    ]
                                );
                            }

                            if (isset($record) && $record) {
                                $keptEducationIds[] = $record->id;
                            }
                        }
                    }
                    $student->educationHistories()->whereNotIn('id', $keptEducationIds)->delete();
                } else {
                    $student->educationHistories()->delete();
                }
            }

            if (!empty($validated['health'])) {
                $student->health()->updateOrCreate(
                    ['student_id' => $student->id],
                    $validated['health']
                );
            }

           if (array_key_exists('socials', $validated)) {
                $keptSocialIds = [];

                $uniqueSocials = collect($validated['socials'] ?? [])
                    ->filter(fn ($item) => !empty($item['social_platform_id']))
                    ->unique('social_platform_id');

                foreach ($uniqueSocials as $social) {
                    // 1. Cari data termasuk yang sudah di-soft-delete
                    $record = \App\Models\StudentSocial::withTrashed()->updateOrCreate(
                        [
                            'student_id'         => $student->id,
                            'social_platform_id' => $social['social_platform_id'],
                        ],
                        [
                            'username'   => $social['username'] ?? null,
                            'url'        => $social['url'] ?? null,
                            'is_public'  => $social['is_public'] ?? false,
                            'is_primary' => $social['is_primary'] ?? false,
                        ]
                    );

                    // 2. Jika ternyata record tersebut dulunya ter-soft-delete, restore kembali!
                    if ($record->trashed()) {
                        $record->restore();
                    }

                    $keptSocialIds[] = $record->id;
                }

                // 3. Soft delete data yang tidak dicentang/dipilih lagi di UI
                $student->socials()->whereNotIn('id', $keptSocialIds)->delete();
            }
            

            if (array_key_exists('achievements', $validated)) {
                if (!empty($validated['achievements'])) {
                    $keptAchievementIds = [];
                    foreach ($validated['achievements'] as $ach) {
                        if (!empty($ach['title'])) {
                            $record = $student->achievements()->updateOrCreate(
                                [
                                    'id' => $ach['id'] ?? null,
                                    'student_id' => $student->id,
                                ],
                                [
                                    'title'            => $ach['title'],
                                    'organizer'        => $ach['organizer'] ?? null,
                                    'level'            => $ach['level'] ?? null,
                                    'category'         => $ach['category'] ?? null,
                                    'rank'             => $ach['rank'] ?? null,
                                    'achievement_date' => $ach['achievement_date'] ?? null,
                                    'description'      => $ach['description'] ?? null,
                                ]
                            );
                            $keptAchievementIds[] = $record->id;
                        }
                    }
                    $student->achievements()->whereNotIn('id', $keptAchievementIds)->delete();
                } else {
                    $student->achievements()->delete();
                }
            }

            if (array_key_exists('violations', $validated)) {
                if (!empty($validated['violations'])) {
                    $keptViolationIds = [];
                    foreach ($validated['violations'] as $violation) {
                        if (!empty($violation['title'])) {
                            $record = $student->violations()->updateOrCreate(
                                [
                                    'id' => $violation['id'] ?? null,
                                    'student_id' => $student->id,
                                ],
                                [
                                    'title'          => $violation['title'],
                                    'point'          => $violation['point'] ?? 0,
                                    'violation_date' => $violation['violation_date'] ?? null,
                                    'description'    => $violation['description'] ?? null,
                                    'reported_by'    => Auth::id(),
                                ]
                            );
                            $keptViolationIds[] = $record->id;
                        }
                    }
                    $student->violations()->whereNotIn('id', $keptViolationIds)->delete();
                } else {
                    $student->violations()->delete();
                }
            }
            if ($request->hasFile('new_document_file')) {
                $file = $request->file('new_document_file');
                $filePath = $file->store('student_documents', 'public');

                // Ambil default ID jika class DocumentType ada
                $defaultDocTypeId = class_exists(DocumentType::class)
                    ? (DocumentType::value('id') ?? 1)
                    : 1;

                // Pakai $request->filled() agar bernilai benar jika input kosong / null
                $documentTypeId = $request->filled('document_type_id')
                    ? $request->input('document_type_id')
                    : $defaultDocTypeId;

                $student->documents()->create([
                    'document_type_id' => $documentTypeId, // Sudah terjamin tidak NULL
                    'original_name'    => $file->getClientOriginalName(),
                    'stored_name'      => basename($filePath),
                    'file_path'        => $filePath,
                    'extension'        => strtolower($file->getClientOriginalExtension()),
                    'disk'             => 'public',
                    'file_size'        => $file->getSize(),
                    'mime_type'        => $file->getClientMimeType(),
                    'notes'            => $validated['new_document_name'] ?? null,
                    'uploaded_by'      => Auth::id(),
                ]);
            }
        });

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Soft delete data siswa
     */
    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();
        return back()->with('success', 'Data siswa berhasil dipindahkan ke sampah.');
    }

    /**
     * Memulihkan data siswa dari soft delete
     */
    public function restore(int $id): RedirectResponse
    {
        /** @var \App\Models\Student $student */
        $student = Student::onlyTrashed()->findOrFail($id);
        $student->restore();

        return back()->with('success', 'Data siswa berhasil dipulihkan.');
    }

    /**
     * Menghapus data siswa secara permanen beserta berkasnya
     */
    public function forceDelete(int $id): RedirectResponse
    {
        /** @var \App\Models\Student $student */
        $student = Student::onlyTrashed()->with('documents')->findOrFail($id);

        // Hapus file fisik dokumen dari penyimpanan
        foreach ($student->documents as $doc) {
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
        }

        $student->forceDelete();

        return back()->with('success', 'Data siswa berhasil dihapus secara permanen.');
    }
}
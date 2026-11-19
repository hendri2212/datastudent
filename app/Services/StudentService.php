<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentEducationHistory;
use App\Models\StudentSocial;
use App\Models\StudentViolation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentService
{
    public function __construct(private readonly CompressedUploadStorage $uploads) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, ?int $actorId): Student
    {
        return DB::transaction(function () use ($data, $actorId) {
            $this->validateAcademicHierarchy($data);
            $student = Student::create($this->studentAttributes($data));
            $this->syncEnrollment($student, $data);
            $this->syncRelations($student, $data, $actorId, true);
            $this->recordActivity($student, $actorId, 'created', 'Data siswa ditambahkan');

            return $student;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Student $student, array $data, ?int $actorId): Student
    {
        return DB::transaction(function () use ($student, $data, $actorId) {
            $this->validateAcademicHierarchy($data);
            $before = $this->studentChangeSnapshot($student);
            $student->update($this->studentAttributes($data));
            $this->syncEnrollment($student, $data);
            $this->syncRelations($student, $data, $actorId, false);
            $after = $this->studentChangeSnapshot($student->fresh());
            $photoFile = $data['photo_file'] ?? null;
            if ($photoFile instanceof \Illuminate\Http\UploadedFile) {
                $after['Foto siswa'] = 'File baru: '.$photoFile->getClientOriginalName();
            }
            foreach ($data['achievements'] ?? [] as $index => $achievement) {
                $certificate = $achievement['certificate'] ?? null;
                if (! $certificate instanceof \Illuminate\Http\UploadedFile) {
                    continue;
                }

                $achievementId = $achievement['id'] ?? null;
                if ($achievementId === null) {
                    $achievementId = DB::table('student_achievements')
                        ->where('student_id', $student->id)
                        ->where('title', $achievement['title'] ?? '')
                        ->orderByDesc('id')
                        ->value('id') ?? 'baru-'.($index + 1);
                }
                $after["Prestasi #{$achievementId} · Sertifikat"] = 'File baru: '.$certificate->getClientOriginalName();
            }
            $changes = $this->studentChangeDiff($before, $after);
            $this->recordActivity($student, $actorId, 'updated', 'Data siswa diperbarui', $changes);

            return $student;
        });
    }

    /** @param array<string, array{old: string|null, new: string|null}> $changes */
    private function recordActivity(Student $student, ?int $actorId, string $action, string $title, array $changes = []): void
    {
        $request = request();
        $description = $student->full_name;
        if ($changes !== []) {
            $description .= "\n[student_changes]".json_encode($changes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $student->logs()->create([
            'user_id' => $actorId,
            'action' => $action,
            'title' => $title,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /** @return array<string, string|null> */
    private function studentChangeSnapshot(Student $student): array
    {
        $labels = [
            'nis' => 'NIS', 'nisn' => 'NISN', 'full_name' => 'Nama lengkap', 'nickname' => 'Nama panggilan',
            'birth_place' => 'Tempat lahir', 'birth_date' => 'Tanggal lahir', 'phone' => 'Nomor telepon',
            'email' => 'Email', 'address' => 'Alamat', 'postal_code' => 'Kode pos', 'notes' => 'Catatan',
            'is_locked' => 'Status akun', 'gender_id' => 'Jenis kelamin', 'religion_id' => 'Agama',
            'citizenship_id' => 'Kewarganegaraan',
        ];
        $snapshot = [];

        foreach ($labels as $field => $label) {
            $value = $student->getRawOriginal($field);
            if (in_array($field, ['gender_id', 'religion_id', 'citizenship_id'], true)) {
                $table = match ($field) {
                    'gender_id' => 'genders',
                    'religion_id' => 'religions',
                    default => 'citizenships',
                };
                $value = $value ? DB::table($table)->where('id', $value)->value('name') : null;
            } elseif ($field === 'is_locked') {
                $value = $value ? 'Dikunci' : 'Aktif';
            }
            $snapshot[$label] = $value === null ? null : (string) $value;
        }

        $enrollment = $student->currentEnrollment;
        $snapshot['Jurusan'] = $enrollment?->classroom?->major?->name;
        $snapshot['Kelas'] = $enrollment?->classroom?->name;
        $snapshot['Tahun ajaran'] = $enrollment?->academicYear?->name;
        $snapshot['Status siswa'] = $enrollment?->status?->name;
        $photo = $student->getRawOriginal('photo');
        $snapshot['Foto siswa'] = $photo ? 'File: '.basename((string) $photo) : null;

        $this->appendRelatedSnapshot($snapshot, 'Data orang tua / wali', 'student_family', $student->id);
        $this->appendRelatedSnapshot($snapshot, 'Data kesehatan', 'student_healths', $student->id);
        $this->appendRelatedSnapshot($snapshot, 'Riwayat pendidikan', 'student_education_history', $student->id);
        $this->appendRelatedSnapshot($snapshot, 'Media sosial', 'student_socials', $student->id);
        $this->appendRelatedSnapshot($snapshot, 'Prestasi', 'student_achievements', $student->id);
        $this->appendRelatedSnapshot($snapshot, 'Pelanggaran', 'student_violations', $student->id);
        $this->appendRelatedSnapshot($snapshot, 'Dokumen', 'student_documents', $student->id);

        return $snapshot;
    }

    /** @param array<string, string|null> $snapshot */
    private function appendRelatedSnapshot(array &$snapshot, string $section, string $table, int $studentId): void
    {
        foreach (DB::table($table)->where('student_id', $studentId)->get() as $record) {
            $attributes = (array) $record;
            $recordId = (string) ($attributes['id'] ?? '');
            foreach ($attributes as $field => $rawValue) {
                if (in_array($field, ['id', 'student_id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                    continue;
                }
                $value = $this->studentChangeValue($field, $rawValue);
                if ($value !== null) {
                    $snapshot["{$section} #{$recordId} · ".$this->studentChangeLabel($field)] = $value;
                }
            }
            if (array_key_exists('deleted_at', $attributes)) {
                $snapshot["{$section} #{$recordId} · Status data"] = $attributes['deleted_at'] === null ? 'Aktif' : 'Dihapus';
            }
        }
    }

    private function studentChangeValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $lookup = [
            'gender_id' => ['genders', 'name'], 'religion_id' => ['religions', 'name'],
            'citizenship_id' => ['citizenships', 'name'], 'blood_type_id' => ['blood_types', 'name'],
            'father_occupation_id' => ['occupations', 'name'], 'mother_occupation_id' => ['occupations', 'name'],
            'guardian_occupation_id' => ['occupations', 'name'], 'father_income_category_id' => ['income_categories', 'name'],
            'mother_income_category_id' => ['income_categories', 'name'], 'guardian_income_category_id' => ['income_categories', 'name'],
            'relationship_type_id' => ['relationship_types', 'name'], 'education_level_id' => ['education_levels', 'name'],
            'social_platform_id' => ['social_platforms', 'name'], 'document_type_id' => ['document_types', 'name'],
        ];
        if (isset($lookup[$field])) {
            [$table, $column] = $lookup[$field];
            return (string) (DB::table($table)->where('id', $value)->value($column) ?? $value);
        }
        if (in_array($field, ['is_locked', 'is_public', 'is_primary', 'is_graduated', 'is_verified'], true)) {
            return (bool) $value ? 'Ya' : 'Tidak';
        }
        if (in_array($field, ['photo', 'certificate', 'file_path'], true)) {
            return 'File: '.basename((string) $value);
        }

        return (string) $value;
    }

    private function studentChangeLabel(string $field): string
    {
        $labels = [
            'father_name' => 'Nama ayah', 'father_occupation_id' => 'Pekerjaan ayah', 'father_income_category_id' => 'Penghasilan ayah', 'father_phone' => 'Telepon ayah',
            'mother_name' => 'Nama ibu', 'mother_occupation_id' => 'Pekerjaan ibu', 'mother_income_category_id' => 'Penghasilan ibu', 'mother_phone' => 'Telepon ibu',
            'guardian_name' => 'Nama wali', 'guardian_occupation_id' => 'Pekerjaan wali', 'guardian_income_category_id' => 'Penghasilan wali', 'guardian_phone' => 'Telepon wali',
            'emergency_contact_name' => 'Nama kontak darurat', 'emergency_contact_phone' => 'Telepon kontak darurat', 'relationship_type_id' => 'Hubungan dengan siswa',
            'height' => 'Tinggi badan', 'weight' => 'Berat badan', 'blood_type_id' => 'Golongan darah', 'allergies' => 'Alergi', 'medical_history' => 'Riwayat penyakit',
            'disabilities' => 'Disabilitas', 'medications' => 'Obat yang dikonsumsi', 'hospital' => 'Rumah sakit', 'doctor' => 'Dokter',
            'education_level_id' => 'Jenjang pendidikan', 'school_name' => 'Nama sekolah', 'npsn' => 'NPSN', 'entry_year' => 'Tahun masuk',
            'graduation_year' => 'Tahun lulus', 'final_score' => 'Nilai akhir', 'is_graduated' => 'Status kelulusan',
            'social_platform_id' => 'Platform', 'username' => 'Nama akun', 'url' => 'Tautan', 'is_public' => 'Akun publik', 'is_primary' => 'Akun utama',
            'title' => 'Judul', 'organizer' => 'Penyelenggara', 'level' => 'Tingkat', 'category' => 'Kategori', 'rank' => 'Peringkat',
            'achievement_date' => 'Tanggal prestasi', 'certificate' => 'Sertifikat', 'point' => 'Poin', 'violation_date' => 'Tanggal pelanggaran',
            'description' => 'Keterangan', 'document_type_id' => 'Jenis dokumen', 'original_name' => 'Nama berkas', 'file_size' => 'Ukuran berkas',
            'is_verified' => 'Status verifikasi', 'notes' => 'Catatan',
        ];

        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /** @param array<string, string|null> $before
     *  @param array<string, string|null> $after
     *  @return array<string, array{old: string|null, new: string|null}>
     */
    private function studentChangeDiff(array $before, array $after): array
    {
        $changes = [];
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $field) {
            $newValue = $after[$field] ?? null;
            $oldValue = $before[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function studentAttributes(array $data): array
    {
        return Arr::only($data, [
            'school_id', 'major_id', 'classroom_id',
            'user_id', 'gender_id', 'religion_id', 'citizenship_id', 'nis', 'nisn',
            'full_name', 'nickname', 'birth_place', 'birth_date', 'phone', 'email',
            'address', 'postal_code', 'photo', 'is_locked', 'notes',
        ]);
    }

    /** @param array<string, mixed> $data */
    private function syncEnrollment(Student $student, array $data): void
    {
        if (! isset($data['academic_year_id'], $data['classroom_id'], $data['student_status_id'])) {
            return;
        }

        $student->enrollments()
            ->whereNull('ended_at')
            ->where('academic_year_id', '!=', $data['academic_year_id'])
            ->update(['ended_at' => now()->toDateString()]);

        $enrollment = $student->enrollments()->firstOrNew([
            'academic_year_id' => $data['academic_year_id'],
        ]);
        $enrollment->fill([
            'classroom_id' => $data['classroom_id'],
            'student_status_id' => $data['student_status_id'],
            'ended_at' => null,
        ]);
        $enrollment->enrolled_at ??= now();
        $enrollment->save();
    }

    /** @param array<string, mixed> $data */
    private function syncRelations(Student $student, array $data, ?int $actorId, bool $creating): void
    {
        // 1. Data Family (HasOne)
        if (array_key_exists('family', $data) && is_array($data['family'])) {
            $family = $student->family()->withTrashed()->firstOrNew();
            $family->fill($data['family']);
            if ($family->trashed()) {
                $family->restore();
            }
            $family->save();
        }

        // 2. Data Health (HasOne)
        if (array_key_exists('health', $data) && is_array($data['health'])) {
            $health = $student->health()->withTrashed()->firstOrNew();
            $health->fill($data['health']);
            if ($health->trashed()) {
                $health->restore();
            }
            $health->save();
        }

        // 3. Data HasMany Relations
        $this->syncEducation($student, $data);
        $this->syncSocials($student, $data);
        $this->syncAchievements($student, $data);
        $this->syncViolations($student, $data, $actorId);
    }

    /** @param array<string, mixed> $data */
    private function syncEducation(Student $student, array $data): void
    {
        if (! array_key_exists('education_histories', $data)) {
            return;
        }

        $kept = [];
        foreach ($data['education_histories'] ?? [] as $item) {
            $id = $item['id'] ?? null;
            $certificate = $item['certificate'] ?? null;

            if (! empty($id)) {
                /** @var StudentEducationHistory|null $record */
                $record = StudentEducationHistory::withTrashed()
                    ->where('student_id', $student->id)
                    ->where('id', $id)
                    ->first();
            } else {
                $record = new StudentEducationHistory(['student_id' => $student->id]);
            }

            if ($record instanceof StudentEducationHistory) {
                $record->fill(Arr::except($item, ['id', 'certificate']));
                if ($record->trashed()) {
                    $record->restore();
                }
                $record->save();

                if ($certificate instanceof \Illuminate\Http\UploadedFile && $certificate->isValid()) {
                    if ($record->certificate && Storage::disk('local')->exists($record->certificate)) {
                        Storage::disk('local')->delete($record->certificate);
                    }
                    $record->update(['certificate' => $this->uploads->store($certificate, "education_certificates/{$student->id}", 'local')]);
                }

                $kept[] = $record->id;
            }
        }

        $removedHistories = $student->educationHistories()
            ->whereNotIn('id', $kept)
            ->get(['id', 'certificate']);
        $certificatePaths = $removedHistories->pluck('certificate')->filter()->values()->all();

        if ($removedHistories->isNotEmpty()) {
            $student->educationHistories()->whereIn('id', $removedHistories->modelKeys())->delete();
        }

        if ($certificatePaths !== []) {
            DB::afterCommit(static function () use ($certificatePaths): void {
                foreach ($certificatePaths as $path) {
                    if (Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                    }
                }
            });
        }
    }

    /** @param array<string, mixed> $data */
    private function syncSocials(Student $student, array $data): void
    {
        if (! array_key_exists('socials', $data)) {
            return;
        }

        $kept = [];
        foreach ($data['socials'] ?? [] as $item) {
            $id = $item['id'] ?? null;

            if (! empty($id)) {
                /** @var StudentSocial|null $record */
                $record = StudentSocial::withTrashed()
                    ->where('student_id', $student->id)
                    ->where('id', $id)
                    ->first();
            } else {
                $record = new StudentSocial(['student_id' => $student->id]);
            }

            if ($record instanceof StudentSocial) {
                $record->fill(Arr::except($item, ['id']));
                if ($record->trashed()) {
                    $record->restore();
                }
                $record->save();
                $kept[] = $record->id;
            }
        }

        $student->socials()->whereNotIn('id', $kept)->delete();
    }

    /** @param array<string, mixed> $data */
    private function syncAchievements(Student $student, array $data): void
    {
        if (! array_key_exists('achievements', $data)) {
            return;
        }

        $kept = [];
        foreach ($data['achievements'] ?? [] as $item) {
            $id = $item['id'] ?? null;
            $certificate = $item['certificate'] ?? null;

            if (! empty($id)) {
                /** @var StudentAchievement|null $record */
                $record = StudentAchievement::withTrashed()
                    ->where('student_id', $student->id)
                    ->where('id', $id)
                    ->first();
            } else {
                $record = new StudentAchievement(['student_id' => $student->id]);
            }

            if ($record instanceof StudentAchievement) {
                $record->fill(Arr::except($item, ['id', 'certificate']));
                if ($record->trashed()) {
                    $record->restore();
                }
                $record->save();

                if ($certificate instanceof \Illuminate\Http\UploadedFile && $certificate->isValid()) {
                    if ($record->certificate && Storage::disk('public')->exists($record->certificate)) {
                        Storage::disk('public')->delete($record->certificate);
                    }
                    $record->update(['certificate' => $this->uploads->store($certificate, "certificates/{$student->id}", 'public')]);
                }

                $kept[] = $record->id;
            }
        }

        $removedAchievements = $student->achievements()
            ->whereNotIn('id', $kept)
            ->get(['id', 'certificate']);
        $certificatePaths = $removedAchievements->pluck('certificate')->filter()->values()->all();

        if ($removedAchievements->isNotEmpty()) {
            $student->achievements()->whereIn('id', $removedAchievements->modelKeys())->delete();
        }

        if ($certificatePaths !== []) {
            DB::afterCommit(static function () use ($certificatePaths): void {
                foreach ($certificatePaths as $path) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            });
        }
    }

    /** @param array<string, mixed> $data */
    private function syncViolations(Student $student, array $data, ?int $actorId): void
    {
        if (! array_key_exists('violations', $data)) {
            return;
        }

        $kept = [];
        foreach ($data['violations'] ?? [] as $item) {
            $id = $item['id'] ?? null;

            if (! empty($id)) {
                /** @var StudentViolation|null $record */
                $record = StudentViolation::withTrashed()
                    ->where('student_id', $student->id)
                    ->where('id', $id)
                    ->first();
            } else {
                $record = new StudentViolation(['student_id' => $student->id]);
            }

            if ($record instanceof StudentViolation) {
                $record->fill(Arr::except($item, ['id']));
                if ($record->reported_by === null && $actorId !== null && $actorId > 0) {
                    $record->reported_by = $actorId;
                }
                if ($record->trashed()) {
                    $record->restore();
                }
                $record->save();
                $kept[] = $record->id;
            }
        }

        $student->violations()->whereNotIn('id', $kept)->delete();
    }

    /** @param array<string, mixed> $data */
    private function validateAcademicHierarchy(array $data): void
    {
        if (! isset($data['classroom_id'], $data['major_id'], $data['school_id'])) {
            throw ValidationException::withMessages([
                'academic_hierarchy' => 'Data struktur akademik (sekolah, jurusan, kelas) wajib diisi.',
            ]);
        }

        /** @var Classroom|null $classroom */
        $classroom = Classroom::with('major:id,school_id')->find($data['classroom_id']);

        if (! $classroom) {
            throw ValidationException::withMessages(['classroom_id' => 'Kelas tidak ditemukan.']);
        }

        if ((int) $classroom->major_id !== (int) $data['major_id']) {
            throw ValidationException::withMessages(['classroom_id' => 'Kelas tidak termasuk dalam jurusan yang dipilih.']);
        }

        if (! $classroom->major || (int) $classroom->major->school_id !== (int) $data['school_id']) {
            throw ValidationException::withMessages(['school_id' => 'Jurusan dan kelas tidak termasuk dalam sekolah yang dipilih.']);
        }
    }
}

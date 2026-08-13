<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentEducationHistory;
use App\Models\StudentSocial;
use App\Models\StudentViolation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentService
{
    /** @param array<string, mixed> $data */
    public function create(array $data, ?int $actorId): Student
    {
        return DB::transaction(function () use ($data, $actorId) {
            $this->validateAcademicHierarchy($data);
            $student = Student::create($this->studentAttributes($data));
            $this->syncEnrollment($student, $data);
            $this->syncRelations($student, $data, $actorId, true);

            return $student;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Student $student, array $data, ?int $actorId): Student
    {
        return DB::transaction(function () use ($student, $data, $actorId) {
            $this->validateAcademicHierarchy($data);
            $student->update($this->studentAttributes($data));
            $this->syncEnrollment($student, $data);
            $this->syncRelations($student, $data, $actorId, false);

            return $student;
        });
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
                $record->fill(Arr::except($item, ['id']));
                if ($record->trashed()) {
                    $record->restore();
                }
                $record->save();
                $kept[] = $record->id;
            }
        }

        $student->educationHistories()->whereNotIn('id', $kept)->delete();
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
                $record->fill(Arr::except($item, ['id']));
                if ($record->trashed()) {
                    $record->restore();
                }
                $record->save();
                $kept[] = $record->id;
            }
        }

        $student->achievements()->whereNotIn('id', $kept)->delete();
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
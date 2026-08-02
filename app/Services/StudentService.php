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
            'user_id', 'gender_id', 'religion_id', 'citizenship_id', 'nis', 'nisn',
            'full_name', 'nickname', 'birth_place', 'birth_date', 'phone', 'email',
            'address', 'postal_code', 'photo', 'is_locked', 'notes',
        ]);
    }

    /** @param array<string, mixed> $data */
    private function syncEnrollment(Student $student, array $data): void
    {
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
        if (array_key_exists('family', $data) && is_array($data['family'])) {
            $family = $student->family()->withTrashed()->firstOrNew();
            $family->fill($data['family']);
            $family->restore();
            $family->save();
        }

        if (array_key_exists('health', $data) && is_array($data['health'])) {
            $health = $student->health()->withTrashed()->firstOrNew();
            $health->fill($data['health']);
            $health->restore();
            $health->save();
        }

        $this->syncEducation($student, $data, $creating);
        $this->syncSocials($student, $data, $creating);
        $this->syncAchievements($student, $data, $creating);
        $this->syncViolations($student, $data, $actorId, $creating);
    }

    /** @param array<string, mixed> $data */
    private function syncEducation(Student $student, array $data, bool $creating): void
    {
        if (! array_key_exists('education_histories', $data) && ! $creating) {
            return;
        }

        $kept = [];
        foreach ($data['education_histories'] ?? [] as $item) {
            $record = StudentEducationHistory::withTrashed()
                ->where('student_id', $student->id)
                ->when($item['id'] ?? null, fn ($query, $id) => $query->whereKey($id))
                ->when(! ($item['id'] ?? null), fn ($query) => $query->where('education_level_id', $item['education_level_id']))
                ->first() ?? new StudentEducationHistory(['student_id' => $student->id]);
            $record->fill(Arr::except($item, ['id']));
            $record->restore();
            $record->save();
            $kept[] = $record->id;
        }
        $student->educationHistories()->whereNotIn('id', $kept)->delete();
    }

    /** @param array<string, mixed> $data */
    private function syncSocials(Student $student, array $data, bool $creating): void
    {
        if (! array_key_exists('socials', $data) && ! $creating) {
            return;
        }

        $kept = [];
        foreach ($data['socials'] ?? [] as $item) {
            $record = StudentSocial::withTrashed()->firstOrNew([
                'student_id' => $student->id,
                'social_platform_id' => $item['social_platform_id'],
            ]);
            $record->fill(Arr::except($item, ['id']));
            $record->restore();
            $record->save();
            $kept[] = $record->id;
        }
        $student->socials()->whereNotIn('id', $kept)->delete();
    }

    /** @param array<string, mixed> $data */
    private function syncAchievements(Student $student, array $data, bool $creating): void
    {
        if (! array_key_exists('achievements', $data) && ! $creating) {
            return;
        }

        $kept = [];
        foreach ($data['achievements'] ?? [] as $item) {
            $record = isset($item['id'])
                ? StudentAchievement::withTrashed()->where('student_id', $student->id)->whereKey($item['id'])->firstOrFail()
                : new StudentAchievement(['student_id' => $student->id]);
            $record->fill(Arr::except($item, ['id']));
            $record->restore();
            $record->save();
            $kept[] = $record->id;
        }
        $student->achievements()->whereNotIn('id', $kept)->delete();
    }

    /** @param array<string, mixed> $data */
    private function syncViolations(Student $student, array $data, ?int $actorId, bool $creating): void
    {
        if (! array_key_exists('violations', $data) && ! $creating) {
            return;
        }

        $kept = [];
        foreach ($data['violations'] ?? [] as $item) {
            $record = isset($item['id'])
                ? StudentViolation::withTrashed()->where('student_id', $student->id)->whereKey($item['id'])->firstOrFail()
                : new StudentViolation(['student_id' => $student->id]);
            $record->fill(Arr::except($item, ['id']));
            if ($record->reported_by === null && $actorId !== null && $actorId > 0) {
                $record->reported_by = $actorId;
            }
            $record->restore();
            $record->save();
            $kept[] = $record->id;
        }
        $student->violations()->whereNotIn('id', $kept)->delete();
    }

    /** @param array<string, mixed> $data */
    private function validateAcademicHierarchy(array $data): void
    {
        $classroom = Classroom::with('major:id,school_id')->whereKey($data['classroom_id'])->firstOrFail();

        if ($classroom->major_id !== (int) $data['major_id']) {
            throw ValidationException::withMessages(['classroom_id' => 'Kelas tidak termasuk dalam jurusan yang dipilih.']);
        }

        if ($classroom->major->school_id !== (int) $data['school_id']) {
            throw ValidationException::withMessages(['school_id' => 'Jurusan dan kelas tidak termasuk dalam sekolah yang dipilih.']);
        }
    }
}

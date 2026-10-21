<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentVerificationController extends Controller
{
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids' => ['sometimes', 'array', 'min:1', 'max:100'],
            'student_ids.*' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'action' => ['required', 'in:verify,unverify'],
            'filters' => ['sometimes', 'array'],
            'filters.search' => ['nullable', 'string', 'max:100'],
            'filters.classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'filters.major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'filters.academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'filters.citizenship_id' => ['nullable', 'integer', 'exists:citizenships,id'],
            'filters.gender_id' => ['nullable', 'integer', 'exists:genders,id'],
            'filters.religion_id' => ['nullable', 'integer', 'exists:religions,id'],
            'filters.student_status_id' => ['nullable', 'integer', 'exists:student_statuses,id'],
            'filters.blood_type_id' => ['nullable', 'integer', 'exists:blood_types,id'],
            'filters.tab' => ['nullable', 'in:active,trashed'],
        ]);
        $actor = $request->user();
        abort_unless($actor, 401);

        $query = Student::query();
        if (! empty($validated['student_ids'])) {
            $query->whereKey($validated['student_ids']);
        } else {
            $filters = $validated['filters'] ?? [];
            abort_if(($filters['tab'] ?? 'active') === 'trashed', 422, 'Verifikasi massal hanya tersedia untuk siswa aktif.');

            $query
                ->when($filters['search'] ?? null, function ($query, string $search): void {
                    $query->where(function ($students) use ($search): void {
                        $students->where('full_name', 'like', "%{$search}%")
                            ->orWhere('nisn', 'like', "{$search}%")
                            ->orWhere('nis', 'like', "{$search}%")
                            ->orWhere('phone', 'like', "{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->when(collect(['classroom_id', 'major_id', 'academic_year_id', 'student_status_id'])->contains(fn ($key) => ! empty($filters[$key])), function ($query) use ($filters): void {
                    $query->whereHas('enrollments', function ($enrollments) use ($filters): void {
                        $enrollments
                            ->when($filters['classroom_id'] ?? null, fn ($q, $id) => $q->where('classroom_id', $id))
                            ->when($filters['major_id'] ?? null, fn ($q, $id) => $q->whereHas('classroom', fn ($classroom) => $classroom->where('major_id', $id)))
                            ->when($filters['academic_year_id'] ?? null, fn ($q, $id) => $q->where('academic_year_id', $id))
                            ->when($filters['student_status_id'] ?? null, fn ($q, $id) => $q->where('student_status_id', $id));
                    });
                })
                ->when($filters['citizenship_id'] ?? null, fn ($query, $id) => $query->where('citizenship_id', $id))
                ->when($filters['gender_id'] ?? null, fn ($query, $id) => $query->where('gender_id', $id))
                ->when($filters['religion_id'] ?? null, fn ($query, $id) => $query->where('religion_id', $id))
                ->when($filters['blood_type_id'] ?? null, fn ($query, $id) => $query->whereHas('health', fn ($health) => $health->where('blood_type_id', $id)));
        }
        $students = $query->get();
        $changed = 0;

        DB::transaction(function () use ($request, $students, $actor, $validated, &$changed): void {
            foreach ($students as $student) {
                $shouldVerify = $validated['action'] === 'verify';
                if (($student->verified_at !== null) === $shouldVerify) {
                    continue;
                }

                $student->update([
                    'verified_at' => $shouldVerify ? now() : null,
                    'verified_by' => $shouldVerify ? $actor->id : null,
                ]);
                $student->logs()->create([
                    'user_id' => $actor->id,
                    'action' => $shouldVerify ? 'verified' : 'unverified',
                    'title' => $shouldVerify ? 'Data siswa diverifikasi' : 'Verifikasi siswa dibatalkan',
                    'description' => $student->full_name,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                $changed++;
            }
        });

        $label = $validated['action'] === 'verify' ? 'diverifikasi' : 'dibatalkan verifikasinya';
        return back()->with('success', $changed > 0
            ? "Verifikasi {$changed} siswa berhasil {$label}."
            : 'Tidak ada perubahan; status siswa yang dipilih sudah sesuai.');
    }

    public function verify(Request $request, Student $student): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor, 401);

        if ($student->verified_at !== null) {
            return redirect()->back()->withErrors([
                'message' => 'Siswa ini sudah diverifikasi sebelumnya.',
            ]);
        }

        DB::transaction(function () use ($request, $student, $actor): void {
            $student->update([
                'verified_at' => now(),
                'verified_by' => $actor->id,
            ]);
            $student->logs()->create([
                'user_id' => $actor->id,
                'action' => 'verified',
                'title' => 'Data siswa diverifikasi',
                'description' => $student->full_name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->back()->with('success', 'Data siswa berhasil diverifikasi.');
    }

    public function unverify(Request $request, Student $student): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor, 401);

        if ($student->verified_at !== null) {
            DB::transaction(function () use ($request, $student, $actor): void {
                $student->update([
                    'verified_at' => null,
                    'verified_by' => null,
                ]);
                $student->logs()->create([
                    'user_id' => $actor->id,
                    'action' => 'unverified',
                    'title' => 'Verifikasi siswa dibatalkan',
                    'description' => $student->full_name,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        }

        return redirect()->back()->with('success', 'Status verifikasi siswa telah dibatalkan.');
    }
}

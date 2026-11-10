<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Major;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function statisticsDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:birthdays,age'],
            'age_group' => ['nullable', 'integer', 'between:0,3'],
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'major' => ['nullable', 'integer'],
            'classroom' => ['nullable', 'integer'],
        ]);
        $today = Carbon::today();
        $students = Student::query()
            ->whereHas('currentEnrollment')
            ->whereNotNull('birth_date')
            ->whereDate('birth_date', '<=', $today->toDateString())
            ->with(['currentEnrollment.classroom.major', 'gender', 'religion'])
            ->get(['id', 'full_name', 'birth_date', 'gender_id', 'religion_id'])
            ->map(function (Student $student) use ($today): array {
                $birthDate = $student->birth_date;
                $age = $birthDate->age;
                $birthdayThisYear = Carbon::create($today->year, $birthDate->month, 1)
                    ->day(min($birthDate->day, Carbon::create($today->year, $birthDate->month, 1)->daysInMonth));
                $nextBirthday = $birthdayThisYear->lt($today) ? $birthdayThisYear->copy()->addYear() : $birthdayThisYear;

                return [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'age' => $age,
                    'birthdayAge' => $age + ($nextBirthday->year > $today->year ? 1 : 0),
                    'birthDate' => $birthDate->toDateString(),
                    'nextBirthday' => $nextBirthday->toDateString(),
                    'daysUntil' => (int) $today->diffInDays($nextBirthday),
                    'classroomId' => $student->currentEnrollment->classroom->id ?? null,
                    'majorId' => $student->currentEnrollment->classroom->major->id ?? null,
                    'classroom' => $student->currentEnrollment->classroom->name ?? 'Belum ada kelas',
                    'major' => $student->currentEnrollment->classroom->major->name ?? 'Belum ada jurusan',
                    'gender' => $student->gender->name ?? 'Tidak diketahui',
                    'religion' => $student->religion->name ?? 'Tidak diketahui',
                ];
            });

        if ($validated['type'] === 'birthdays') {
            $cohort = $students->filter(fn (array $student) => $student['daysUntil'] <= 30)->values();
        } else {
            $ageGroup = (int) ($validated['age_group'] ?? 0);
            $cohort = $students->filter(fn (array $student) => match ($ageGroup) {
                0 => $student['age'] <= 15,
                1 => $student['age'] >= 16 && $student['age'] <= 17,
                2 => $student['age'] >= 18 && $student['age'] <= 19,
                default => $student['age'] >= 20,
            })->values();
        }

        $majorFilters = $cohort->map(fn (array $student) => ['id' => $student['majorId'], 'name' => $student['major']])
            ->filter(fn (array $major) => $major['id'] !== null)
            ->unique('id')->sortBy('name')->values();
        $classroomFilters = $cohort->map(fn (array $student) => ['id' => $student['classroomId'], 'name' => $student['classroom'], 'majorId' => $student['majorId']])
            ->filter(fn (array $classroom) => $classroom['id'] !== null)
            ->unique('id')->sortBy('name')->values();

        $rows = $cohort->filter(function (array $student) use ($validated): bool {
            if (isset($validated['major']) && (int) $student['majorId'] !== (int) $validated['major']) return false;
            if (isset($validated['classroom']) && (int) $student['classroomId'] !== (int) $validated['classroom']) return false;
            $search = trim((string) ($validated['search'] ?? ''));
            if ($search !== '') {
                $searchable = implode(' ', [$student['name'], $student['classroom'], $student['major'], $student['gender'], $student['religion']]);
                if (mb_stripos($searchable, $search) === false) return false;
            }

            return true;
        });

        if ($validated['type'] === 'birthdays') {
            $rows = $rows->sortBy(fn (array $student) => sprintf('%03d:%s', $student['daysUntil'], mb_strtolower($student['name'])))->values();
            $summary = null;
        } else {
            $rows = $rows->sortBy('name')->values();
            $summary = [];
            foreach (['classroom' => 'kelas', 'major' => 'jurusan', 'gender' => 'kelamin', 'religion' => 'agama'] as $field => $label) {
                $summary[$label] = $rows->countBy($field)
                    ->map(fn (int $total, string $name) => ['name' => $name, 'total' => $total])
                    ->sortByDesc('total')
                    ->values();
            }
        }

        $perPage = 20;
        $page = (int) ($validated['page'] ?? 1);
        $total = $rows->count();

        return response()->json([
            'data' => $rows->forPage($page, $perPage)->values(),
            'summary' => $summary,
            'filters' => ['majors' => $majorFilters, 'classrooms' => $classroomFilters],
            'currentPage' => $page,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
        ]);
    }

    public function activities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'role' => ['nullable', 'in:student,admin,operator'],
        ]);

        $logs = StudentLog::query()
            ->with([
                'student' => fn ($query) => $query->withTrashed()->select('id', 'full_name'),
                'user:id,name,role',
            ])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereIn('student_id', Student::withTrashed()->select('id')->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->whereHas('user', fn ($user) => $user->where('role', $role)))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'data' => $logs->getCollection()->map(fn (StudentLog $log) => [
                'id' => $log->id,
                'name' => $log->student->full_name ?? $log->description ?? 'Siswa',
                'action' => $log->title,
                'date' => $log->created_at?->toIso8601String(),
                'type' => $log->action,
                'actorName' => $log->user->name ?? 'Sistem',
                'actorRole' => $log->user->role->value ?? 'system',
                'description' => $log->description,
                'changes' => $log->changes,
            ])->values(),
            'currentPage' => $logs->currentPage(),
            'lastPage' => $logs->lastPage(),
            'total' => $logs->total(),
        ]);
    }

    public function __invoke(): Response
    {
        $students = Student::query()->whereHas('currentEnrollment');
        $totalStudents = (clone $students)->count();
        $today = Carbon::today();
        $studentDates = (clone $students)
            ->whereNotNull('birth_date')
            ->whereDate('birth_date', '<=', $today->toDateString())
            ->get(['id', 'full_name', 'birth_date']);

        $ageGroups = [
            ['label' => '15 tahun ke bawah', 'total' => 0],
            ['label' => '16–17 tahun', 'total' => 0],
            ['label' => '18–19 tahun', 'total' => 0],
            ['label' => '20 tahun ke atas', 'total' => 0],
        ];
        $ageTotal = 0;
        $ageSum = 0;
        $birthdays = [];

        foreach ($studentDates as $student) {
            $birthDate = $student->birth_date;
            $age = $birthDate->age;
            $ageGroupIndex = match (true) {
                $age <= 15 => 0,
                $age <= 17 => 1,
                $age <= 19 => 2,
                default => 3,
            };
            $ageGroups[$ageGroupIndex]['total']++;
            $ageTotal++;
            $ageSum += $age;

            $birthdayMonth = Carbon::create($today->year, $birthDate->month, 1);
            $birthdayThisYear = $birthdayMonth->copy()->day(min($birthDate->day, $birthdayMonth->daysInMonth));
            $nextBirthday = $birthdayThisYear->lt($today) ? $birthdayThisYear->copy()->addYear() : $birthdayThisYear;
            $daysUntilBirthday = $today->diffInDays($nextBirthday);
            if ($daysUntilBirthday <= 30) {
                $birthdays[] = [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'date' => $nextBirthday->toDateString(),
                    'age' => $age + ($nextBirthday->year > $today->year ? 1 : 0),
                    'daysUntil' => $daysUntilBirthday,
                ];
            }

        }
        usort($birthdays, fn ($a, $b) => $a['daysUntil'] <=> $b['daysUntil']);
        $birthdays = array_slice($birthdays, 0, 8);

        $activities = StudentLog::query()
            ->with([
                'student' => fn ($query) => $query->withTrashed()->select('id', 'full_name'),
                'user:id,name,role',
            ])
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (StudentLog $log) => [
                'id' => $log->id,
                'name' => $log->student->full_name ?? $log->description ?? 'Siswa',
                'action' => $log->title,
                'date' => $log->created_at?->toIso8601String(),
                'type' => $log->action,
                'actorName' => $log->user->name ?? 'Sistem',
                'actorRole' => $log->user->role->value ?? 'system',
            ]);

        $genderCounts = (clone $students)
            ->join('genders', 'students.gender_id', '=', 'genders.id')
            ->select('genders.name', DB::raw('count(*) as total'))
            ->groupBy('genders.name')
            ->orderBy('genders.name')
            ->get()
            ->map(fn (Student $row) => ['name' => (string) $row->getAttribute('name'), 'total' => (int) $row->getAttribute('total')]);

        $religionTotals = (clone $students)
            ->join('religions', 'students.religion_id', '=', 'religions.id')
            ->select('religions.id', 'religions.name', DB::raw('count(*) as total'))
            ->groupBy('religions.id', 'religions.name')
            ->pluck('total', 'id');
        $religionCounts = Religion::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($religion) => ['name' => $religion->name, 'total' => (int) ($religionTotals[$religion->id] ?? 0)]);

        $majorTotals = DB::table('student_enrollments')
            ->join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->join('classrooms', 'student_enrollments.classroom_id', '=', 'classrooms.id')
            ->whereNull('student_enrollments.ended_at')
            ->whereNull('students.deleted_at')
            ->whereNull('classrooms.deleted_at')
            ->where('classrooms.status', 'Aktif')
            ->whereRaw('student_enrollments.id = (select max(current_enrollment.id) from student_enrollments as current_enrollment where current_enrollment.student_id = student_enrollments.student_id and current_enrollment.ended_at is null)')
            ->select('classrooms.major_id', DB::raw('count(distinct students.id) as total'))
            ->groupBy('classrooms.major_id')
            ->pluck('total', 'major_id');
        $majorCounts = Major::query()
            ->where('status', 'Aktif')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($major) => ['id' => $major->id, 'name' => $major->name, 'code' => $major->code, 'total' => (int) ($majorTotals[$major->id] ?? 0)]);

        /** @var \Illuminate\Support\Collection<int, object{classroom_id:int, major_id:int, gender:string, religion:string, total:int}> $details */
        $details = DB::table('student_enrollments')
            ->join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->join('classrooms', 'student_enrollments.classroom_id', '=', 'classrooms.id')
            ->join('genders', 'students.gender_id', '=', 'genders.id')
            ->join('religions', 'students.religion_id', '=', 'religions.id')
            ->whereNull('student_enrollments.ended_at')
            ->whereNull('students.deleted_at')
            ->whereNull('classrooms.deleted_at')
            ->where('classrooms.status', 'Aktif')
            ->whereRaw('student_enrollments.id = (select max(current_enrollment.id) from student_enrollments as current_enrollment where current_enrollment.student_id = student_enrollments.student_id and current_enrollment.ended_at is null)')
            ->select('classrooms.id as classroom_id', 'classrooms.major_id', 'genders.name as gender', 'religions.name as religion', DB::raw('count(distinct students.id) as total'))
            ->groupBy('classrooms.id', 'classrooms.major_id', 'genders.name', 'religions.name')
            ->get();

        $demographicsByClass = [];
        $demographicsByMajor = [];
        $addDemographics = function (array &$collection, int $key, string $gender, string $religion, int $total): void {
            $collection[$key] ??= ['genders' => [], 'religions' => []];
            $collection[$key]['genders'][$gender] = ($collection[$key]['genders'][$gender] ?? 0) + $total;
            $collection[$key]['religions'][$religion] = ($collection[$key]['religions'][$religion] ?? 0) + $total;
        };
        foreach ($details as $row) {
            $gender = (string) data_get($row, 'gender');
            $religion = (string) data_get($row, 'religion');
            $total = (int) data_get($row, 'total');
            $addDemographics($demographicsByClass, (int) data_get($row, 'classroom_id'), $gender, $religion, $total);
            $addDemographics($demographicsByMajor, (int) data_get($row, 'major_id'), $gender, $religion, $total);
        }

        $formatDemographicValues = static function (array $counts): array {
            $formatted = [];
            foreach ($counts as $name => $total) {
                $formatted[] = ['name' => (string) $name, 'total' => (int) $total];
            }

            return $formatted;
        };
        $formatDemographics = fn (array $data) => [
            'genders' => $formatDemographicValues($data['genders'] ?? []),
            'religions' => $formatDemographicValues($data['religions'] ?? []),
        ];
        $majorCounts = $majorCounts->map(function ($major) use ($demographicsByMajor, $formatDemographics) {
            $major['demographics'] = $formatDemographics($demographicsByMajor[$major['id']] ?? []);
            return $major;
        });

        $maleTotal = 0;
        $femaleTotal = 0;
        foreach ($genderCounts as $gender) {
            $normalizedName = mb_strtolower($gender['name']);
            if (in_array($normalizedName, ['laki-laki', 'laki laki', 'pria', 'male'], true)) {
                $maleTotal = $gender['total'];
            }
            if (in_array($normalizedName, ['perempuan', 'wanita', 'female'], true)) {
                $femaleTotal = $gender['total'];
            }
        }

        $classrooms = Classroom::query()
            ->where('status', 'Aktif')
            ->withCount(['enrollments as student_count' => fn ($query) => $query->whereNull('ended_at')
                ->whereHas('student')
                ->whereRaw('student_enrollments.id = (select max(current_enrollment.id) from student_enrollments as current_enrollment where current_enrollment.student_id = student_enrollments.student_id and current_enrollment.ended_at is null)')])
            ->with('major:id,name')
            ->orderByRaw("CASE level WHEN 'X' THEN 1 WHEN 'XI' THEN 2 WHEN 'XII' THEN 3 ELSE 4 END")
            ->orderBy('rombel')
            ->get(['id', 'name', 'level', 'rombel', 'major_id'])
            ->map(fn ($classroom) => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'level' => $classroom->level,
                'major' => $classroom->major?->name,
                'total' => (int) $classroom->student_count,
                'demographics' => $formatDemographics($demographicsByClass[$classroom->id] ?? []),
            ]);

        return Inertia::render('Dashboard', [
            'summary' => [
                'total' => $totalStudents,
                'male' => $maleTotal,
                'female' => $femaleTotal,
                'classes' => $classrooms->count(),
                'verified' => (clone $students)->whereNotNull('verified_at')->count(),
                'pending' => (clone $students)->whereNull('verified_at')->count(),
                'averageAge' => $ageTotal ? round($ageSum / $ageTotal, 1) : 0,
            ],
            'ageGroups' => $ageGroups,
            'upcomingBirthdays' => $birthdays,
            'recentActivity' => $activities,
            'genderCounts' => $genderCounts,
            'religionCounts' => $religionCounts,
            'majorCounts' => $majorCounts,
            'classrooms' => $classrooms,
        ]);
    }
}

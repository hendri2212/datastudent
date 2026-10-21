<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Major;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
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
                'name' => $log->student?->full_name ?? $log->description ?? 'Siswa',
                'action' => $log->title,
                'date' => $log->created_at?->toIso8601String(),
                'type' => $log->action,
                'actorName' => $log->user?->name ?? 'Sistem',
                'actorRole' => $log->user?->role?->value ?? 'system',
            ]);

        $genderCounts = (clone $students)
            ->join('genders', 'students.gender_id', '=', 'genders.id')
            ->select('genders.name', DB::raw('count(*) as total'))
            ->groupBy('genders.name')
            ->orderBy('genders.name')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'total' => (int) $row->total]);

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
        $addDemographics = function (array &$collection, int $key, object $row): void {
            $collection[$key] ??= ['genders' => [], 'religions' => []];
            $collection[$key]['genders'][$row->gender] = ($collection[$key]['genders'][$row->gender] ?? 0) + (int) $row->total;
            $collection[$key]['religions'][$row->religion] = ($collection[$key]['religions'][$row->religion] ?? 0) + (int) $row->total;
        };
        foreach ($details as $row) {
            $addDemographics($demographicsByClass, (int) $row->classroom_id, $row);
            $addDemographics($demographicsByMajor, (int) $row->major_id, $row);
        }

        $formatDemographics = fn (array $data) => [
            'genders' => collect($data['genders'] ?? [])->map(fn ($total, $name) => ['name' => $name, 'total' => $total])->values(),
            'religions' => collect($data['religions'] ?? [])->map(fn ($total, $name) => ['name' => $name, 'total' => $total])->values(),
        ];
        $majorCounts = $majorCounts->map(function ($major) use ($demographicsByMajor, $formatDemographics) {
            $major['demographics'] = $formatDemographics($demographicsByMajor[$major['id']] ?? []);
            return $major;
        });

        $classrooms = Classroom::query()
            ->where('status', 'Aktif')
            ->withCount(['enrollments as student_count' => fn ($query) => $query->whereNull('ended_at')
                ->whereHas('student')
                ->whereRaw('student_enrollments.id = (select max(current_enrollment.id) from student_enrollments as current_enrollment where current_enrollment.student_id = student_enrollments.student_id and current_enrollment.ended_at is null)')])
            ->with('major:id,name')
            ->orderByRaw("FIELD(level, 'X', 'XI', 'XII')")
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
                'male' => (int) $genderCounts->first(fn ($gender) => in_array(mb_strtolower($gender['name']), ['laki-laki', 'laki laki', 'pria', 'male']))['total'] ?? 0,
                'female' => (int) $genderCounts->first(fn ($gender) => in_array(mb_strtolower($gender['name']), ['perempuan', 'wanita', 'female']))['total'] ?? 0,
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

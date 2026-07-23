<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Major;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    /**
     * Menampilkan daftar kelas
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $tab = $request->input('tab', 'active');

        $query = Classroom::query()->with(['major', 'students.religion', 'students.gender']);

        if ($tab === 'trashed') {
            $query->onlyTrashed();
        } else {
            $query->withoutTrashed();
        }

        $classrooms = $query
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('level', 'like', "%{$search}%")
                        ->orWhere('rombel', 'like', "%{$search}%")
                        ->orWhereHas('major', function ($m) use ($search) {
                            $m->where('name', 'like', "%{$search}%")
                              ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('level')
            ->orderBy('major_id')
            ->orderBy('rombel')
            ->get()
            ->map(function (Classroom $classroom) {
                $students = $classroom->students;

                return [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'level' => $classroom->level,
                    'rombel' => $classroom->rombel,
                    'status' => $classroom->status ?? 'Aktif',
                    'deleted_at' => $classroom->deleted_at?->toIso8601String(),
                    'major' => [
                        'id' => $classroom->major->id ?? 0,
                        'name' => $classroom->major->name ?? '-',
                        'code' => $classroom->major->code ?? '-',
                    ],
                    'studentCount' => $students->count(),
                    'maleCount' => $students->where('gender.name', 'Laki-laki')->count(),
                    'femaleCount' => $students->where('gender.name', 'Perempuan')->count(),
                    'religion' => [
                        'islam' => $students->where('religion.name', 'Islam')->count(),
                        'kristen' => $students->where('religion.name', 'Kristen')->count(),
                        'katolik' => $students->where('religion.name', 'Katolik')->count(),
                        'hindu' => $students->where('religion.name', 'Hindu')->count(),
                        'buddha' => $students->where('religion.name', 'Buddha')->count(),
                        'khonghucu' => $students->where('religion.name', 'Khonghucu')->count(),
                    ],
                ];
            });

        return Inertia::render('classrooms/Index', [
            'classrooms' => $classrooms,
            'majors' => Major::select('id', 'name', 'code')->get(),
            'filters' => [
                'search' => $search ?? '',
                'tab' => $tab,
            ],
            'trashedCount' => Classroom::onlyTrashed()->count(),
        ]);
    }

    /**
     * Tambah Kelas Baru (Auto Increment Rombel)
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'major_id' => ['required', 'exists:majors,id'],
            'level' => ['required', 'in:X,XI,XII'],
        ]);

        /** @var Major $major */
        $major = Major::findOrFail($validated['major_id']);

        $lastRombel = Classroom::withTrashed()
            ->where('major_id', $validated['major_id'])
            ->where('level', $validated['level'])
            ->max('rombel');

        $nextRombel = $lastRombel ? $lastRombel + 1 : 1;

        Classroom::create([
            'major_id' => $validated['major_id'],
            'level' => $validated['level'],
            'rombel' => $nextRombel,
            'name' => "{$validated['level']} {$major->code} {$nextRombel}",
            'status' => 'Aktif',
        ]);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    /**
     * Update Kelas
     */
    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        $validated = $request->validate([
            'major_id' => ['required', 'exists:majors,id'],
            'level' => ['required', 'in:X,XI,XII'],
            'rombel' => ['required', 'integer', 'min:1', 'max:20'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
        ]);

        /** @var Major $major */
        $major = Major::findOrFail($validated['major_id']);

        $classroom->update([
            'major_id' => $validated['major_id'],
            'level' => $validated['level'],
            'rombel' => $validated['rombel'],
            'name' => "{$validated['level']} {$major->code} {$validated['rombel']}",
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    /**
     * Soft Delete Kelas
     */
    public function destroy(Classroom $classroom): RedirectResponse
    {
        $classroom->delete();

        return back()->with('success', 'Kelas dipindahkan ke tempat sampah.');
    }

    /**
     * Restore Kelas
     */
    public function restore(int $id): RedirectResponse
    {
        $classroom = Classroom::onlyTrashed()->findOrFail($id);
        $classroom->restore();

        return back()->with('success', 'Kelas berhasil dipulihkan.');
    }

    /**
     * Force Delete Kelas
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $classroom = Classroom::onlyTrashed()->findOrFail($id);
        $classroom->forceDelete();

        return back()->with('success', 'Kelas dihapus secara permanen.');
    }
}
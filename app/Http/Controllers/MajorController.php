<?php

namespace App\Http\Controllers;

use App\Models\Major;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MajorController extends Controller
{
    public function index(Request $request): Response
    {
        // Ambil school_id dari user yang sedang login (sesuaikan jika belum ada relasi school_id)
        $schoolId = $request->user()->school_id ?? 1;

        $search = $request->input('search');

        $majors = Major::query()
            ->where('school_id', $schoolId)
            ->withCount('students')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get()
            ->map(fn ($major) => [
                'id' => $major->id,
                'code' => $major->code,
                'name' => $major->name,
                'status' => $major->status,
                'studentCount' => $major->students_count ?? 0,
            ]);

        return Inertia::render('majors/Index', [
            'majors' => $majors,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $request->user()->school_id ?? 1;

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                "unique:majors,code,NULL,id,school_id,{$schoolId}",
            ],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
        ]);

        Major::create([
            'school_id' => $schoolId,
            ...$validated,
        ]);

        return back()->with('success', 'Jurusan berhasil ditambahkan.');
    }

    public function update(Request $request, Major $major)
    {
        $schoolId = $request->user()->school_id ?? 1;

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                "unique:majors,code,{$major->id},id,school_id,{$schoolId}",
            ],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
        ]);

        $major->update($validated);

        return back()->with('success', 'Jurusan berhasil diperbarui.');
    }

    public function destroy(Major $major)
    {
        $major->delete();

        return back()->with('success', 'Jurusan berhasil dihapus.');
    }
}
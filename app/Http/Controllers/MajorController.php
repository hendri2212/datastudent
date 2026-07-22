<?php

namespace App\Http\Controllers;

use App\Models\Major;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MajorController extends Controller
{
    public function index(): Response
    {
        // Ambil data jurusan beserta relasi siswa/sekolah
        $majors = Major::withCount('students')
            ->get()
            ->map(function ($major) {
                return [
                    'id' => $major->id,
                    'code' => $major->code,
                    'name' => $major->name,
                    'status' => $major->status ?? 'Aktif',
                    'studentCount' => $major->students_count ?? 0,
                    'school_id' => $major->school_id,
                ];
            });

        return Inertia::render('majors/Index', [
            'majors' => $majors,
            'schools' => School::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Ambil school_id default dari request atau ambil ID sekolah pertama
        $defaultSchoolId = School::value('id');

        $validated = $request->validate([
            'school_id' => ['nullable', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
        ]);

        // Pastikan school_id selalu terisi
        $validated['school_id'] = $validated['school_id'] ?? $defaultSchoolId;

        Major::create($validated);

        return back()->with('success', 'Jurusan berhasil ditambahkan.');
    }

    public function update(Request $request, Major $major): RedirectResponse
    {
        $validated = $request->validate([
            'school_id' => ['nullable', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
        ]);

        $major->update(array_filter($validated));

        return back()->with('success', 'Jurusan berhasil diperbarui.');
    }

    public function destroy(Major $major): RedirectResponse
    {
        $major->delete();

        return back()->with('success', 'Jurusan berhasil dihapus.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAchievement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentAchievementController extends Controller
{
    public function store(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'rank' => ['nullable', 'integer'],
            'achievement_date' => ['nullable', 'date'],
            'certificate' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $student->achievements()->create($validated);

        return back()->with('success', 'Prestasi siswa berhasil ditambahkan.');
    }

    public function update(Request $request, StudentAchievement $achievement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'rank' => ['nullable', 'integer'],
            'achievement_date' => ['nullable', 'date'],
            'certificate' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $achievement->update($validated);

        return back()->with('success', 'Prestasi siswa berhasil diperbarui.');
    }

    public function destroy(StudentAchievement $achievement): RedirectResponse
    {
        $achievement->delete();

        return back()->with('success', 'Prestasi siswa berhasil dihapus.');
    }
}
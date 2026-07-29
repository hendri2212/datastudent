<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentEducationHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentEducationHistoryController extends Controller
{
    /**
     * Menyimpan riwayat pendidikan baru untuk siswa tertentu
     */
    public function store(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'education_level_id' => ['required', 'exists:education_levels,id'],
            'school_name'        => ['required', 'string', 'max:255'],
            'npsn'               => ['nullable', 'string', 'max:30'],
            'address'            => ['nullable', 'string'],
            'entry_year'         => ['nullable', 'integer', 'digits:4'],
            'graduation_year'    => ['nullable', 'integer', 'digits:4', 'gte:entry_year'],
            'final_score'        => ['nullable', 'numeric', 'between:0,100.00'],
            'is_graduated'       => ['boolean'],
            'notes'              => ['nullable', 'string'],
        ]);

        $student->educationHistories()->create($validated);

        return back()->with('success', 'Riwayat pendidikan berhasil ditambahkan.');
    }

    /**
     * Memperbarui riwayat pendidikan siswa
     */
    public function update(Request $request, StudentEducationHistory $studentEducationHistory): RedirectResponse
    {
        $validated = $request->validate([
            'education_level_id' => ['required', 'exists:education_levels,id'],
            'school_name'        => ['required', 'string', 'max:255'],
            'npsn'               => ['nullable', 'string', 'max:30'],
            'address'            => ['nullable', 'string'],
            'entry_year'         => ['nullable', 'integer', 'digits:4'],
            'graduation_year'    => ['nullable', 'integer', 'digits:4', 'gte:entry_year'],
            'final_score'        => ['nullable', 'numeric', 'between:0,100.00'],
            'is_graduated'       => ['boolean'],
            'notes'              => ['nullable', 'string'],
        ]);

        $studentEducationHistory->update($validated);

        return back()->with('success', 'Riwayat pendidikan berhasil diperbarui.');
    }

    /**
     * Menghapus riwayat pendidikan (Soft Delete)
     */
    public function destroy(StudentEducationHistory $studentEducationHistory): RedirectResponse
    {
        $studentEducationHistory->delete();

        return back()->with('success', 'Riwayat pendidikan berhasil dihapus.');
    }
}
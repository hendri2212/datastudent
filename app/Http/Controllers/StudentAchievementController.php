<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAchievement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class StudentAchievementController extends Controller
{
    public function store(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'organizer'        => ['nullable', 'string', 'max:255'],
            'level'            => ['nullable', 'string', 'max:100'],
            'category'         => ['nullable', 'string', 'max:100'],
            'rank'             => ['nullable', 'integer'],
            'achievement_date' => ['nullable', 'date'],
            'certificate'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description'      => ['nullable', 'string'],
        ]);

        if ($request->hasFile('certificate')) {
            $validated['certificate'] = $request->file('certificate')->store('certificates', 'public');
        }

        $student->achievements()->create($validated);

        return back()->with('success', 'Prestasi siswa berhasil ditambahkan.');
    }

    public function update(Request $request, StudentAchievement $achievement): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'organizer'        => ['nullable', 'string', 'max:255'],
            'level'            => ['nullable', 'string', 'max:100'],
            'category'         => ['nullable', 'string', 'max:100'],
            'rank'             => ['nullable', 'integer'],
            'achievement_date' => ['nullable', 'date'],
            'certificate'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description'      => ['nullable', 'string'],
        ]);

        if ($request->hasFile('certificate')) {
            // Hapus file sertifikat lama jika ada
            if ($achievement->certificate && Storage::disk('public')->exists($achievement->certificate)) {
                Storage::disk('public')->delete($achievement->certificate);
            }

            $validated['certificate'] = $request->file('certificate')->store('certificates', 'public');
        }

        $achievement->update($validated);

        return back()->with('success', 'Prestasi siswa berhasil diperbarui.');
    }

    public function destroy(StudentAchievement $achievement): RedirectResponse
    {
        // Hapus file dari disk storage saat data dihapus
        if ($achievement->certificate && Storage::disk('public')->exists($achievement->certificate)) {
            Storage::disk('public')->delete($achievement->certificate);
        }

        $achievement->delete();

        return back()->with('success', 'Prestasi siswa berhasil dihapus.');
    }

    public function downloadCertificate(StudentAchievement $achievement): BinaryFileResponse
    {
        if (!$achievement->certificate || !Storage::disk('public')->exists($achievement->certificate)) {
            abort(404, 'File sertifikat tidak ditemukan.');
        }

        $filePath = Storage::disk('public')->path($achievement->certificate);

        return response()->download($filePath);
    }
}
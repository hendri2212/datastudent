<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentSocial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentSocialController extends Controller
{
    /**
     * Menyimpan media sosial baru milik siswa
     */
    public function store(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'social_platform_id' => ['required', 'exists:social_platforms,id'],
            'username'           => ['nullable', 'string', 'max:100'],
            'url'                => ['nullable', 'url', 'max:255'],
            'is_public'          => ['boolean'],
            'is_primary'         => ['boolean'],
        ]);

        $student->socials()->create($validated);

        return back()->with('success', 'Media sosial siswa berhasil ditambahkan.');
    }

    /**
     * Memperbarui media sosial siswa
     */
    public function update(Request $request, StudentSocial $studentSocial): RedirectResponse
    {
        $validated = $request->validate([
            'social_platform_id' => ['required', 'exists:social_platforms,id'],
            'username'           => ['nullable', 'string', 'max:100'],
            'url'                => ['nullable', 'url', 'max:255'],
            'is_public'          => ['boolean'],
            'is_primary'         => ['boolean'],
        ]);

        $studentSocial->update($validated);

        return back()->with('success', 'Media sosial siswa berhasil diperbarui.');
    }

    /**
     * Menghapus media sosial siswa (Soft Delete)
     */
    public function destroy(StudentSocial $studentSocial): RedirectResponse
    {
        $studentSocial->delete();

        return back()->with('success', 'Media sosial siswa berhasil dihapus.');
    }
}
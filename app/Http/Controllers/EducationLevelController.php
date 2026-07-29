<?php

namespace App\Http\Controllers;

use App\Models\EducationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EducationLevelController extends Controller
{
    /**
     * Menampilkan daftar jenjang pendidikan
     */
    public function index(): Response
    {
        $educationLevels = EducationLevel::orderBy('sort_order', 'asc')->get();

        return Inertia::render('master/education-levels/Index', [
            'educationLevels' => $educationLevels,
        ]);
    }

    /**
     * Menyimpan jenjang pendidikan baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255', 'unique:education_levels,name'],
            'sort_order' => ['required', 'integer', 'min:1'],
        ]);

        EducationLevel::create($validated);

        return back()->with('success', 'Jenjang pendidikan berhasil ditambahkan.');
    }

    /**
     * Memperbarui data jenjang pendidikan
     */
    public function update(Request $request, EducationLevel $educationLevel): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255', 'unique:education_levels,name,' . $educationLevel->id],
            'sort_order' => ['required', 'integer', 'min:1'],
        ]);

        $educationLevel->update($validated);

        return back()->with('success', 'Jenjang pendidikan berhasil diperbarui.');
    }

    /**
     * Menghapus jenjang pendidikan
     */
    public function destroy(EducationLevel $educationLevel): RedirectResponse
    {
        $educationLevel->delete();

        return back()->with('success', 'Jenjang pendidikan berhasil dihapus.');
    }
}
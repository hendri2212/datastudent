<?php

namespace App\Http\Controllers;

use App\Models\Religion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReligionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $religions = Religion::query()
            ->withCount('students')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->get();

        return Inertia::render('master/religions/Index', [
            'religions' => $religions,
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:religions,name'],
        ]);

        Religion::create($validated);

        return back()->with('success', 'Data agama berhasil ditambahkan.');
    }

    public function update(Request $request, Religion $religion): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:religions,name,' . $religion->id],
        ]);

        $religion->update($validated);

        return back()->with('success', 'Data agama berhasil diperbarui.');
    }

    public function destroy(Religion $religion): RedirectResponse
    {
        if ($religion->students()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus agama karena masih digunakan oleh data siswa.');
        }

        $religion->delete();

        return back()->with('success', 'Data agama berhasil dihapus.');
    }
}
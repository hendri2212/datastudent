<?php

namespace App\Http\Controllers;

use App\Models\Gender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GenderController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $genders = Gender::query()
            ->withCount('students')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            })
            ->get();

        return Inertia::render('master/genders/Index', [
            'genders' => $genders,
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:genders,code'],
            'name' => ['required', 'string', 'max:50', 'unique:genders,name'],
        ]);

        Gender::create($validated);

        return back()->with('success', 'Data jenis kelamin berhasil ditambahkan.');
    }

    public function update(Request $request, Gender $gender): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:genders,code,' . $gender->id],
            'name' => ['required', 'string', 'max:50', 'unique:genders,name,' . $gender->id],
        ]);

        $gender->update($validated);

        return back()->with('success', 'Data jenis kelamin berhasil diperbarui.');
    }

    public function destroy(Gender $gender): RedirectResponse
    {
        if ($gender->students()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus jenis kelamin karena masih digunakan oleh data siswa.');
        }

        $gender->delete();

        return back()->with('success', 'Data jenis kelamin berhasil dihapus.');
    }
}
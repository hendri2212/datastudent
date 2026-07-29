<?php

namespace App\Http\Controllers;

use App\Models\Occupation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OccupationController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $occupations = Occupation::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->get();

        return Inertia::render('master/occupations/Index', [
            'occupations' => $occupations,
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:occupations,name'],
        ]);

        Occupation::create($validated);

        return back()->with('success', 'Data pekerjaan berhasil ditambahkan.');
    }

    public function update(Request $request, Occupation $occupation): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:occupations,name,' . $occupation->id],
        ]);

        $occupation->update($validated);

        return back()->with('success', 'Data pekerjaan berhasil diperbarui.');
    }

    public function destroy(Occupation $occupation): RedirectResponse
    {
        $occupation->delete();

        return back()->with('success', 'Data pekerjaan berhasil dihapus.');
    }
}
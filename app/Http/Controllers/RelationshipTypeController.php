<?php

namespace App\Http\Controllers;

use App\Models\RelationshipType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RelationshipTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $relationshipTypes = RelationshipType::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->get();

        return Inertia::render('master/relationship-types/Index', [
            'relationshipTypes' => $relationshipTypes,
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:relationship_types,name'],
        ]);

        RelationshipType::create($validated);

        return back()->with('success', 'Jenis hubungan keluarga berhasil ditambahkan.');
    }

    public function update(Request $request, RelationshipType $relationshipType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:relationship_types,name,' . $relationshipType->id],
        ]);

        $relationshipType->update($validated);

        return back()->with('success', 'Jenis hubungan keluarga berhasil diperbarui.');
    }

    public function destroy(RelationshipType $relationshipType): RedirectResponse
    {
        $relationshipType->delete();

        return back()->with('success', 'Jenis hubungan keluarga berhasil dihapus.');
    }
}
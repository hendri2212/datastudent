<?php

namespace App\Http\Controllers;

use App\Models\Citizenship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenshipController extends Controller
{
    public function index(): JsonResponse
    {
        $citizenships = Citizenship::all();
        return response()->json($citizenships);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:citizenships,name',
        ]);

        $citizenship = Citizenship::create($validated);

        return response()->json([
            'message' => 'Data kewarganegaraan berhasil ditambahkan.',
            'data' => $citizenship
        ], 201);
    }

    public function show(Citizenship $citizenship): JsonResponse
    {
        return response()->json($citizenship);
    }

    public function update(Request $request, Citizenship $citizenship): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:citizenships,name,' . $citizenship->id,
        ]);

        $citizenship->update($validated);

        return response()->json([
            'message' => 'Data kewarganegaraan berhasil diperbarui.',
            'data' => $citizenship
        ]);
    }

    public function destroy(Citizenship $citizenship): JsonResponse
    {
        $citizenship->delete();

        return response()->json([
            'message' => 'Data kewarganegaraan berhasil dihapus.'
        ]);
    }
}
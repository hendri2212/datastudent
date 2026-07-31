<?php

namespace App\Http\Controllers;

use App\Models\BloodType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BloodTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $bloodTypes = BloodType::all();
        return response()->json($bloodTypes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:10|unique:blood_types,name',
        ]);

        $bloodType = BloodType::create($validated);

        return response()->json([
            'message' => 'Golongan darah berhasil ditambahkan.',
            'data' => $bloodType
        ], 201);
    }

    public function show(BloodType $bloodType): JsonResponse
    {
        return response()->json($bloodType);
    }

    public function update(Request $request, BloodType $bloodType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:10|unique:blood_types,name,' . $bloodType->id,
        ]);

        $bloodType->update($validated);

        return response()->json([
            'message' => 'Golongan darah berhasil diperbarui.',
            'data' => $bloodType
        ]);
    }

    public function destroy(BloodType $bloodType): JsonResponse
    {
        $bloodType->delete();

        return response()->json([
            'message' => 'Golongan darah berhasil dihapus.'
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\BloodType;
use Illuminate\Http\Request;

class BloodTypeController extends Controller
{
    public function index()
    {
        $bloodTypes = BloodType::all();
        return response()->json($bloodTypes);
    }

    public function store(Request $request)
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

    public function show(BloodType $bloodType)
    {
        return response()->json($bloodType);
    }

    public function update(Request $request, BloodType $bloodType)
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

    public function destroy(BloodType $bloodType)
    {
        $bloodType->delete();

        return response()->json([
            'message' => 'Golongan darah berhasil dihapus.'
        ]);
    }
}
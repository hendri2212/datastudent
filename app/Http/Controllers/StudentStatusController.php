<?php

namespace App\Http\Controllers;

use App\Models\StudentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentStatusController extends Controller
{
    public function index(): JsonResponse
    {
        $statuses = StudentStatus::all();
        return response()->json($statuses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:student_statuses,name',
        ]);

        $status = StudentStatus::create($validated);

        return response()->json([
            'message' => 'Status siswa berhasil ditambahkan.',
            'data' => $status
        ], 201);
    }

    public function show(StudentStatus $studentStatus): JsonResponse
    {
        return response()->json($studentStatus);
    }

    public function update(Request $request, StudentStatus $studentStatus): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:student_statuses,name,' . $studentStatus->id,
        ]);

        $studentStatus->update($validated);

        return response()->json([
            'message' => 'Status siswa berhasil diperbarui.',
            'data' => $studentStatus
        ]);
    }

    public function destroy(StudentStatus $studentStatus): JsonResponse
    {
        $studentStatus->delete();

        return response()->json([
            'message' => 'Status siswa berhasil dihapus.'
        ]);
    }
}
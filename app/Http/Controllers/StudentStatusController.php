<?php

namespace App\Http\Controllers;

use App\Models\StudentStatus;
use Illuminate\Http\Request;

class StudentStatusController extends Controller
{
    public function index()
    {
        $statuses = StudentStatus::all();
        return response()->json($statuses);
    }

    public function store(Request $request)
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

    public function show(StudentStatus $studentStatus)
    {
        return response()->json($studentStatus);
    }

    public function update(Request $request, StudentStatus $studentStatus)
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

    public function destroy(StudentStatus $studentStatus)
    {
        $studentStatus->delete();

        return response()->json([
            'message' => 'Status siswa berhasil dihapus.'
        ]);
    }
}
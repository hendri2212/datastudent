<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentHealthController extends Controller
{
    public function storeOrUpdate(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'height' => ['nullable', 'numeric', 'between:0,300'],
            'weight' => ['nullable', 'numeric', 'between:0,500'],
            'blood_type_id' => ['nullable', 'exists:blood_types,id'],
            'allergies' => ['nullable', 'string'],
            'medical_history' => ['nullable', 'string'],
            'disabilities' => ['nullable', 'string'],
            'medications' => ['nullable', 'string'],
            'hospital' => ['nullable', 'string', 'max:255'],
            'doctor' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $student->health()->updateOrCreate(
            ['student_id' => $student->id],
            $validated
        );

        return back()->with('success', 'Data kesehatan siswa berhasil disimpan.');
    }
}
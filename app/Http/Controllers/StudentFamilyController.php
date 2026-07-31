<?php

namespace App\Http\Controllers;

use App\Models\IncomeCategory;
use App\Models\Occupation;
use App\Models\RelationshipType;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentFamilyController extends Controller
{
    /**
     * Menampilkan form edit data keluarga siswa.
     */
    public function edit(Student $student): Response
    {
        $student->load([
            'family.fatherOccupation',
            'family.fatherIncomeCategory',
            'family.motherOccupation',
            'family.motherIncomeCategory',
            'family.guardianOccupation',
            'family.guardianIncomeCategory',
            'family.relationshipType',
        ]);

        return Inertia::render('students/family/Edit', [
            'student'           => $student,
            'occupations'       => Occupation::select('id', 'name')->get(),
            'incomeCategories'  => IncomeCategory::select('id', 'name')->get(),
            'relationshipTypes' => RelationshipType::select('id', 'name')->get(),
        ]);
    }

    /**
     * Memperbarui data keluarga siswa.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            // Data Ayah
            'father_name'               => ['nullable', 'string', 'max:255'],
            'father_occupation_id'      => ['nullable', 'exists:occupations,id'],
            'father_income_category_id' => ['nullable', 'exists:income_categories,id'],
            'father_phone'              => ['nullable', 'string', 'max:25'],

            // Data Ibu
            'mother_name'               => ['nullable', 'string', 'max:255'],
            'mother_occupation_id'      => ['nullable', 'exists:occupations,id'],
            'mother_income_category_id' => ['nullable', 'exists:income_categories,id'],
            'mother_phone'              => ['nullable', 'string', 'max:25'],

            // Data Wali
            'guardian_name'               => ['nullable', 'string', 'max:255'],
            'guardian_occupation_id'      => ['nullable', 'exists:occupations,id'],
            'guardian_income_category_id' => ['nullable', 'exists:income_categories,id'],
            'guardian_phone'              => ['nullable', 'string', 'max:25'],

            // Kontak Darurat & Hubungan
            'emergency_contact_name'  => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:25'],
            'relationship_type_id'    => ['nullable', 'exists:relationship_types,id'],

            // Catatan
            'notes'                   => ['nullable', 'string'],
        ]);

        $student->family()->updateOrCreate(
            ['student_id' => $student->id],
            $validated
        );

        return back()->with('success', 'Data keluarga siswa berhasil diperbarui.');
    }
}
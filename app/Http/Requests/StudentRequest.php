<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-students') || $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $student = $this->route('student');
        $studentId = $student instanceof Student ? $student->id : null;

        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'major_id' => ['required', 'integer', 'exists:majors,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'student_status_id' => ['required', 'integer', 'exists:student_statuses,id'],
            'gender_id' => ['required', 'integer', 'exists:genders,id'],
            'religion_id' => ['required', 'integer', 'exists:religions,id'],
            'citizenship_id' => ['nullable', 'integer', 'exists:citizenships,id'],
            'blood_type_id' => ['nullable', 'integer', 'exists:blood_types,id'],
            'nisn' => ['required', 'digits:10', Rule::unique('students', 'nisn')->ignore($studentId)],
            'nis' => ['required', 'string', 'max:30', Rule::unique('students', 'nis')->ignore($studentId)],
            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],

            'family' => ['nullable', 'array'],
            'family.father_name' => ['nullable', 'string', 'max:255'],
            'family.father_occupation_id' => ['nullable', 'integer', 'exists:occupations,id'],
            'family.father_income_category_id' => ['nullable', 'integer', 'exists:income_categories,id'],
            'family.father_phone' => ['nullable', 'string', 'max:25'],
            'family.mother_name' => ['nullable', 'string', 'max:255'],
            'family.mother_occupation_id' => ['nullable', 'integer', 'exists:occupations,id'],
            'family.mother_income_category_id' => ['nullable', 'integer', 'exists:income_categories,id'],
            'family.mother_phone' => ['nullable', 'string', 'max:25'],
            'family.guardian_name' => ['nullable', 'string', 'max:255'],
            'family.guardian_occupation_id' => ['nullable', 'integer', 'exists:occupations,id'],
            'family.guardian_income_category_id' => ['nullable', 'integer', 'exists:income_categories,id'],
            'family.guardian_phone' => ['nullable', 'string', 'max:25'],
            'family.emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'family.emergency_contact_phone' => ['nullable', 'string', 'max:25'],
            'family.relationship_type_id' => ['nullable', 'integer', 'exists:relationship_types,id'],
            'family.notes' => ['nullable', 'string'],

            'health' => ['nullable', 'array'],
            'health.blood_type_id' => ['nullable', 'integer', 'exists:blood_types,id'],
            'health.height' => ['nullable', 'numeric', 'gt:0', 'max:300'],
            'health.weight' => ['nullable', 'numeric', 'gt:0', 'max:500'],
            'health.allergies' => ['nullable', 'string'],
            'health.medical_history' => ['nullable', 'string'],
            'health.disabilities' => ['nullable', 'string'],
            'health.medications' => ['nullable', 'string'],
            'health.hospital' => ['nullable', 'string', 'max:255'],
            'health.doctor' => ['nullable', 'string', 'max:255'],
            'health.notes' => ['nullable', 'string'],

            'education_histories' => ['nullable', 'array'],
            'education_histories.*.id' => ['nullable', 'integer'],
            'education_histories.*.education_level_id' => ['required', 'integer', 'distinct', 'exists:education_levels,id'],
            'education_histories.*.school_name' => ['required', 'string', 'max:255'],
            'education_histories.*.npsn' => ['nullable', 'string', 'max:30'],
            'education_histories.*.address' => ['nullable', 'string'],
            'education_histories.*.entry_year' => ['nullable', 'integer', 'digits:4'],
            'education_histories.*.graduation_year' => ['nullable', 'integer', 'digits:4'],
            'education_histories.*.final_score' => ['nullable', 'numeric', 'between:0,100'],
            'education_histories.*.is_graduated' => ['boolean'],
            'education_histories.*.notes' => ['nullable', 'string'],

            'socials' => ['nullable', 'array'],
            'socials.*.id' => ['nullable', 'integer'],
            'socials.*.social_platform_id' => ['required', 'integer', 'distinct', 'exists:social_platforms,id'],
            'socials.*.username' => ['nullable', 'string', 'max:100'],
            'socials.*.url' => ['nullable', 'url', 'max:255'],
            'socials.*.is_public' => ['boolean'],
            'socials.*.is_primary' => ['boolean'],

            'achievements' => ['nullable', 'array'],
            'achievements.*.id' => ['nullable', 'integer'],
            'achievements.*.title' => ['required', 'string', 'max:255'],
            'achievements.*.organizer' => ['nullable', 'string', 'max:255'],
            'achievements.*.level' => ['nullable', 'string', 'max:100'],
            'achievements.*.category' => ['nullable', 'string', 'max:100'],
            'achievements.*.rank' => ['nullable', 'integer', 'min:1'],
            'achievements.*.achievement_date' => ['nullable', 'date'],
            'achievements.*.description' => ['nullable', 'string'],

            'violations' => ['nullable', 'array'],
            'violations.*.id' => ['nullable', 'integer'],
            'violations.*.title' => ['required', 'string', 'max:255'],
            'violations.*.point' => ['nullable', 'integer', 'min:0'],
            'violations.*.violation_date' => ['nullable', 'date'],
            'violations.*.description' => ['nullable', 'string'],

            'photo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'document_type_id' => ['nullable', 'required_with:new_document_file', 'integer', 'exists:document_types,id'],
            'new_document_name' => ['nullable', 'string', 'max:255'],
            'new_document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function ($validator) {
            $entryYear = $this->input('education_histories', []);

            foreach (is_array($entryYear) ? $entryYear : [] as $index => $history) {
                if (($history['entry_year'] ?? null) && ($history['graduation_year'] ?? null)
                    && $history['graduation_year'] < $history['entry_year']) {
                    $validator->errors()->add(
                        "education_histories.{$index}.graduation_year",
                        'Tahun lulus tidak boleh sebelum tahun masuk.',
                    );
                }
            }
        }];
    }
}

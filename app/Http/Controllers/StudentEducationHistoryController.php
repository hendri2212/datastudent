<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentEducationHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentEducationHistoryController extends Controller
{
    public function preview(Request $request, StudentEducationHistory $studentEducationHistory): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeCertificateAccess($request, $studentEducationHistory);

        abort_unless($studentEducationHistory->certificate && Storage::disk('local')->exists($studentEducationHistory->certificate), 404);

        $path = Storage::disk('local')->path($studentEducationHistory->certificate);

        return response()->file($path, [
            'Content-Type' => mime_content_type($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    public function download(Request $request, StudentEducationHistory $studentEducationHistory): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeCertificateAccess($request, $studentEducationHistory);

        abort_unless($studentEducationHistory->certificate && Storage::disk('local')->exists($studentEducationHistory->certificate), 404);

        return response()->download(
            Storage::disk('local')->path($studentEducationHistory->certificate),
            basename($studentEducationHistory->certificate),
        );
    }

    private function authorizeCertificateAccess(Request $request, StudentEducationHistory $studentEducationHistory): void
    {
        $student = $studentEducationHistory->student;
        $user = $request->user();
        $isStudentOwner = $user
            && ($student->user_id === $user->id
                || (filled($user->email) && filled($student->email)
                    && strcasecmp((string) $user->email, (string) $student->email) === 0));

        abort_unless($user && ($user->can('manage-students') || $isStudentOwner), 403);
    }

    /**
     * Menyimpan riwayat pendidikan baru untuk siswa tertentu
     */
    public function store(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'education_level_id' => ['required', 'exists:education_levels,id'],
            'school_name'        => ['required', 'string', 'max:255'],
            'npsn'               => ['nullable', 'string', 'max:30'],
            'address'            => ['nullable', 'string'],
            'entry_year'         => ['nullable', 'integer', 'digits:4'],
            'graduation_year'    => ['nullable', 'integer', 'digits:4', 'gte:entry_year'],
            'final_score'        => ['nullable', 'numeric', 'between:0,100.00'],
            'is_graduated'       => ['boolean'],
            'notes'              => ['nullable', 'string'],
            'certificate'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $certificate = $validated['certificate'] ?? null;
        unset($validated['certificate']);
        $history = $student->educationHistories()->create($validated);
        if ($certificate) {
            $history->update(['certificate' => $certificate->store("education_certificates/{$student->id}", 'local')]);
        }

        return back()->with('success', 'Riwayat pendidikan berhasil ditambahkan.');
    }

    /**
     * Memperbarui riwayat pendidikan siswa
     */
    public function update(Request $request, StudentEducationHistory $studentEducationHistory): RedirectResponse
    {
        $validated = $request->validate([
            'education_level_id' => ['required', 'exists:education_levels,id'],
            'school_name'        => ['required', 'string', 'max:255'],
            'npsn'               => ['nullable', 'string', 'max:30'],
            'address'            => ['nullable', 'string'],
            'entry_year'         => ['nullable', 'integer', 'digits:4'],
            'graduation_year'    => ['nullable', 'integer', 'digits:4', 'gte:entry_year'],
            'final_score'        => ['nullable', 'numeric', 'between:0,100.00'],
            'is_graduated'       => ['boolean'],
            'notes'              => ['nullable', 'string'],
            'certificate'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $certificate = $validated['certificate'] ?? null;
        unset($validated['certificate']);
        $studentEducationHistory->update($validated);
        if ($certificate) {
            if ($studentEducationHistory->certificate && Storage::disk('local')->exists($studentEducationHistory->certificate)) {
                Storage::disk('local')->delete($studentEducationHistory->certificate);
            }
            $studentEducationHistory->update(['certificate' => $certificate->store("education_certificates/{$studentEducationHistory->student_id}", 'local')]);
        }

        return back()->with('success', 'Riwayat pendidikan berhasil diperbarui.');
    }

    /**
     * Menghapus riwayat pendidikan (Soft Delete)
     */
    public function destroy(StudentEducationHistory $studentEducationHistory): RedirectResponse
    {
        $studentEducationHistory->delete();

        return back()->with('success', 'Riwayat pendidikan berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\StudentDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentDocumentController extends Controller
{
    public function __construct(private readonly StudentDocumentService $documents) {}

    /**
     * Menyimpan / Mengunggah berkas dokumen siswa.
     */
    public function store(Request $request, Student $student): RedirectResponse
    {
        $request->validate([
            'document_type_id' => ['required', 'exists:document_types,id'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // Maksimal 5MB
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $this->documents->upload(
            $student,
            $request->integer('document_type_id'),
            $file,
            $request->string('notes')->toString() ?: null,
            $request->user()?->id,
        );

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    /**
     * Mengunduh berkas dokumen siswa.
     */
    public function download(Student $student, StudentDocument $document): BinaryFileResponse
    {
        if ($document->student_id !== $student->id) {
            abort(404, 'Dokumen tidak ditemukan untuk siswa ini.');
        }

        $disk = $document->disk ?? 'private';

        if (! Storage::disk($disk)->exists($document->file_path)) {
            abort(404, 'Berkas fisik tidak ditemukan di penyimpanan.');
        }

        $fullPath = Storage::disk($disk)->path($document->file_path);

        return response()->download($fullPath, $document->original_name);
    }

    /**
     * Preview berkas dokumen di browser tab baru (PDF / Gambar).
     */
    public function preview(Student $student, StudentDocument $document): BinaryFileResponse
    {
        if ($document->student_id !== $student->id) {
            abort(404, 'Dokumen tidak ditemukan untuk siswa ini.');
        }

        $disk = $document->disk ?? 'private';

        if (! Storage::disk($disk)->exists($document->file_path)) {
            abort(404, 'Berkas fisik tidak ditemukan di penyimpanan.');
        }

        $fullPath = Storage::disk($disk)->path($document->file_path);

        // Menggunakan response()->file() langsung untuk menghindari warning Intelephense
        return response()->file($fullPath, [
            'Content-Type' => $document->mime_type ?? mime_content_type($fullPath),
            'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
        ]);
    }

    /**
     * Verifikasi dokumen oleh Admin / Petugas.
     */
    public function verify(Request $request, Student $student, StudentDocument $document): RedirectResponse
    {
        if ($document->student_id !== $student->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $document->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Dokumen berhasil diverifikasi.');
    }

    /**
     * Menghapus dokumen (Soft Delete sesuai migrasi).
     */
    public function destroy(Student $student, StudentDocument $document): RedirectResponse
    {
        if ($document->student_id !== $student->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $this->documents->delete($document);

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}

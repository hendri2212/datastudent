<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentDocumentController extends Controller
{
    /**
     * Menyimpan / Mengunggah berkas dokumen siswa.
     */
    public function store(Request $request, Student $student): RedirectResponse
    {
        $request->validate([
            'document_type_id' => ['required', 'exists:document_types,id'],
            'file'             => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // Maksimal 5MB
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        // Menentukan disk penyimpanan (sesuaikan dengan kebutuhan sistem Anda: 'public' atau 'private')
        $disk = 'public';

        // Simpan file ke direktori storage
        $filePath = $file->store('student_documents/' . $student->id, $disk);

        // Cari dokumen lama jika siswa sudah pernah upload tipe dokumen yang sama
        // (Mencegah error 'Duplicate Entry' pada constraint UNIQUE: student_id + document_type_id)
        $existingDoc = $student->documents()
            ->where('document_type_id', $request->document_type_id)
            ->first();

        if ($existingDoc) {
            // Hapus file fisik lama jika ada di storage
            $oldDisk = $existingDoc->disk ?? $disk;
            if ($existingDoc->file_path && Storage::disk($oldDisk)->exists($existingDoc->file_path)) {
                Storage::disk($oldDisk)->delete($existingDoc->file_path);
            }
        }

        // Simpan atau Perbarui data di database
        $student->documents()->updateOrCreate(
            [
                'student_id'       => $student->id,
                'document_type_id' => $request->document_type_id,
            ],
            [
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => basename($filePath),
                'file_path'     => $filePath,
                'disk'          => $disk,
                'mime_type'     => $file->getClientMimeType(),
                'file_size'     => $file->getSize(),
                'extension'     => strtolower($file->getClientOriginalExtension()),
                'checksum'      => hash_file('sha256', $file->getRealPath()),
                'uploaded_by'   => auth()->id(),
                'notes'         => $request->notes,
                'is_verified'   => false, // Reset status verifikasi saat file diunggah ulang
                'verified_at'   => null,
                'verified_by'   => null,
            ]
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

        if (!Storage::disk($disk)->exists($document->file_path)) {
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

        if (!Storage::disk($disk)->exists($document->file_path)) {
            abort(404, 'Berkas fisik tidak ditemukan di penyimpanan.');
        }

        $fullPath = Storage::disk($disk)->path($document->file_path);

        // Menggunakan response()->file() langsung untuk menghindari warning Intelephense
        return response()->file($fullPath, [
            'Content-Type'        => $document->mime_type ?? mime_content_type($fullPath),
            'Content-Disposition' => 'inline; filename="' . $document->original_name . '"',
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
            'verified_by' => auth()->id(),
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

        // Hapus file fisik dari storage
        $disk = $document->disk ?? 'private';
        if ($document->file_path && Storage::disk($disk)->exists($document->file_path)) {
            Storage::disk($disk)->delete($document->file_path);
        }

        // Melakukan Soft Delete pada record database
        $document->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}
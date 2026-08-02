<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StudentDocumentService
{
    public function upload(
        Student $student,
        int $documentTypeId,
        UploadedFile $file,
        ?string $notes,
        ?int $uploadedBy,
        string $disk = 'public',
    ): StudentDocument {
        $newPath = $file->store("student_documents/{$student->id}", $disk);

        if ($newPath === false) {
            throw new \RuntimeException('Gagal menyimpan berkas dokumen.');
        }

        $oldPath = null;
        $oldDisk = null;

        try {
            $document = DB::transaction(function () use (
                $student,
                $documentTypeId,
                $file,
                $notes,
                $uploadedBy,
                $disk,
                $newPath,
                &$oldPath,
                &$oldDisk,
            ) {
                $document = StudentDocument::withTrashed()
                    ->where('student_id', $student->id)
                    ->where('document_type_id', $documentTypeId)
                    ->lockForUpdate()
                    ->first();

                if ($document !== null) {
                    $oldPath = $document->file_path;
                    $oldDisk = $document->disk;
                    $document->restore();
                } else {
                    $document = new StudentDocument([
                        'student_id' => $student->id,
                        'document_type_id' => $documentTypeId,
                    ]);
                }

                $document->fill([
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => basename($newPath),
                    'file_path' => $newPath,
                    'disk' => $disk,
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'extension' => strtolower($file->getClientOriginalExtension()),
                    'checksum' => hash_file('sha256', $file->getRealPath()),
                    'uploaded_by' => $uploadedBy,
                    'notes' => $notes,
                    'is_verified' => false,
                    'verified_at' => null,
                    'verified_by' => null,
                ])->save();

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($newPath);

            throw $exception;
        }

        if ($oldPath !== null && $oldPath !== $newPath) {
            Storage::disk($oldDisk ?: $disk)->delete($oldPath);
        }

        return $document;
    }

    public function delete(StudentDocument $document): void
    {
        $path = $document->file_path;
        $disk = $document->disk ?: 'private';

        DB::transaction(fn () => $document->delete());

        if ($path) {
            Storage::disk($disk)->delete($path);
        }
    }
}

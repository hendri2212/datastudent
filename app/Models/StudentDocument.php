<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class StudentDocument extends Model
{
    use SoftDeletes;

    /**
     * Atribut yang secara otomatis ditambahkan ke serialisasi model.
     *
     * @var list<string>
     */
    protected $appends = ['url'];

    /**
     * Nama tabel yang digunakan oleh model ini.
     *
     * @var string
     */
    protected $table = 'student_documents';

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignment).
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'document_type_id',
        'original_name',
        'stored_name',
        'file_path',
        'disk',
        'mime_type',
        'file_size',
        'extension',
        'checksum',
        'is_verified',
        'verified_at',
        'verified_by',
        'uploaded_by',
        'notes',
    ];

    /**
     * Casting tipe data untuk atribut.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'file_size'   => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    /**
     * Nilai default atribut model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'disk'        => 'private',
        'is_verified' => false,
    ];

    /**
     * URL publik untuk mengakses berkas melalui disk storage.
     *
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        if (($this->disk ?? 'public') !== 'public') {
            return null;
        }

        return asset('storage/' . ltrim($this->file_path, '/'));
    }

    /*
    |--------------------------------------------------------------------------
    | Relations (Relasi Database)
    |--------------------------------------------------------------------------
    */

    /**
     * Relasi ke model Student (Siswa pemilik dokumen).
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi ke model DocumentType (Jenis dokumen, misal: KK, Akta, Ijazah).
     *
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * Relasi ke model User (Petugas/Admin yang memverifikasi).
     *
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Relasi ke model User (User/Admin yang mengunggah berkas).
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper / Accessors (Properti Tambahan)
    |--------------------------------------------------------------------------
    */

    /**
     * Format ukuran file agar mudah dibaca (contoh: 2.5 MB, 500 KB).
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return '1 byte';
        }

        return '0 bytes';
    }
}
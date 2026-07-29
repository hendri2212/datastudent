<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_locked'   => 'boolean',
        'verified_at' => 'datetime',
        'birth_date'  => 'date',
    ];

    protected $appends = ['student_status'];

    public function getStudentStatusAttribute()
    {
        return $this->status;
    }

    /**
     * Relasi ke Sekolah
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Relasi ke Jurusan
     */
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    /**
     * Relasi ke Kelas
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Relasi ke Tahun Ajaran
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Relasi ke Status Siswa (Aktif, Lulus, Dikeluarkan, dll)
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(StudentStatus::class, 'student_status_id');
    }

    /**
     * Relasi ke Kewarganegaraan
     */
    public function citizenship(): BelongsTo
    {
        return $this->belongsTo(Citizenship::class);
    }

    /**
     * Relasi ke Jenis Kelamin
     */
    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * Relasi ke Agama
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Relasi ke Golongan Darah
     */
    public function bloodType(): BelongsTo
    {
        return $this->belongsTo(BloodType::class);
    }

    /**
     * Relasi ke Akun User Login Siswa
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke User (Petugas/Admin) yang memverifikasi data siswa
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Relasi ke Data Keluarga Siswa
     */
    public function family(): HasOne
    {
        return $this->hasOne(StudentFamily::class);
    }

    /**
     * Relasi ke Riwayat Sekolah Asal Siswa
     */
    public function schoolHistory(): HasMany
    {
        return $this->hasMany(StudentEducationHistory::class);
    }

    /**
     * Relasi ke Riwayat Pendidikan Siswa
     */
    public function educationHistories(): HasMany
    {
        return $this->hasMany(StudentEducationHistory::class);
    }

    /**
     * Relasi ke Data Kesehatan Siswa
     */
    public function health(): HasOne
    {
        return $this->hasOne(StudentHealth::class);
    }

    /**
     * Relasi ke Prestasi Siswa
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class);
    }

    /**
     * Relasi ke Media Sosial Siswa
     */
    public function socials(): HasMany
    {
        return $this->hasMany(StudentSocial::class);
    }

    /**
     * Relasi ke Pelanggaran Siswa
     */
    public function violations(): HasMany
    {
        return $this->hasMany(StudentViolation::class);
    }

    /**
     * Relasi ke Dokumen / Berkas Siswa
     */
    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }
}
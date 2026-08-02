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
        'is_locked' => 'boolean',
        'verified_at' => 'datetime',
        'birth_date' => 'date',
    ];

    protected $appends = ['student_status'];

    public function getStudentStatusAttribute(): ?StudentStatus
    {
        return $this->status;
    }

    /**
     * Relasi ke Sekolah
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Relasi ke Jurusan
     *
     * @return BelongsTo<Major, $this>
     */
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    /**
     * Relasi ke Kelas
     *
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Relasi ke Tahun Ajaran
     *
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Relasi ke Status Siswa (Aktif, Lulus, Dikeluarkan, dll)
     *
     * @return BelongsTo<StudentStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(StudentStatus::class, 'student_status_id');
    }

    /**
     * Relasi ke Kewarganegaraan
     *
     * @return BelongsTo<Citizenship, $this>
     */
    public function citizenship(): BelongsTo
    {
        return $this->belongsTo(Citizenship::class);
    }

    /**
     * Relasi ke Jenis Kelamin
     *
     * @return BelongsTo<Gender, $this>
     */
    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * Relasi ke Agama
     *
     * @return BelongsTo<Religion, $this>
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Relasi ke Golongan Darah
     *
     * @return BelongsTo<BloodType, $this>
     */
    public function bloodType(): BelongsTo
    {
        return $this->belongsTo(BloodType::class);
    }

    /**
     * Relasi ke Akun User Login Siswa
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke User (Petugas/Admin) yang memverifikasi data siswa
     *
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Relasi ke Data Keluarga Siswa
     *
     * @return HasOne<StudentFamily, $this>
     */
    public function family(): HasOne
    {
        return $this->hasOne(StudentFamily::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)->latestOfMany();
    }

    /**
     * Relasi ke Riwayat Sekolah Asal Siswa
     *
     * @return HasMany<StudentEducationHistory, $this>
     */
    public function schoolHistory(): HasMany
    {
        return $this->hasMany(StudentEducationHistory::class);
    }

    /**
     * Relasi ke Riwayat Pendidikan Siswa
     *
     * @return HasMany<StudentEducationHistory, $this>
     */
    public function educationHistories(): HasMany
    {
        return $this->hasMany(StudentEducationHistory::class);
    }

    /**
     * Relasi ke Data Kesehatan Siswa
     *
     * @return HasOne<StudentHealth, $this>
     */
    public function health(): HasOne
    {
        return $this->hasOne(StudentHealth::class);
    }

    /**
     * Relasi ke Prestasi Siswa
     *
     * @return HasMany<StudentAchievement, $this>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class);
    }

    /**
     * Relasi ke Media Sosial Siswa
     *
     * @return HasMany<StudentSocial, $this>
     */
    public function socials(): HasMany
    {
        return $this->hasMany(StudentSocial::class);
    }

    /**
     * Relasi ke Pelanggaran Siswa
     *
     * @return HasMany<StudentViolation, $this>
     */
    public function violations(): HasMany
    {
        return $this->hasMany(StudentViolation::class);
    }

    /**
     * Relasi ke Dokumen / Berkas Siswa
     *
     * @return HasMany<StudentDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }
}

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

    protected $appends = [
        'school_id',
        'major_id',
        'classroom_id',
        'academic_year_id',
        'student_status_id',
        'blood_type_id',
        'school',
        'major',
        'classroom',
        'academic_year',
        'student_status',
        'photo_url',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'verified_at' => 'datetime',
        'birth_date' => 'date',
    ];

    public function getSchoolIdAttribute(): ?int
    {
        return $this->currentEnrollment?->classroom?->major?->school_id;
    }

    public function getMajorIdAttribute(): ?int
    {
        return $this->currentEnrollment?->classroom?->major_id;
    }

    public function getClassroomIdAttribute(): ?int
    {
        return $this->currentEnrollment?->classroom_id;
    }

    public function getAcademicYearIdAttribute(): ?int
    {
        return $this->currentEnrollment?->academic_year_id;
    }

    public function getStudentStatusIdAttribute(): ?int
    {
        return $this->currentEnrollment?->student_status_id;
    }

    public function getBloodTypeIdAttribute(): ?int
    {
        return $this->health?->blood_type_id;
    }

    public function getSchoolAttribute(): ?School
    {
        return $this->currentEnrollment?->classroom?->major?->school;
    }

    public function getMajorAttribute(): ?Major
    {
        return $this->currentEnrollment?->classroom?->major;
    }

    public function getClassroomAttribute(): ?Classroom
    {
        return $this->currentEnrollment?->classroom;
    }

    public function getAcademicYearAttribute(): ?AcademicYear
    {
        return $this->currentEnrollment?->academicYear;
    }

    public function getStudentStatusAttribute(): ?StudentStatus
    {
        return $this->currentEnrollment?->status;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        return route('students.photo', [
            'student' => $this->id,
            't' => $this->updated_at->timestamp ?? time(),
        ]);
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

    /** @return HasMany<StudentEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /** @return HasOne<StudentEnrollment, $this> */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)
            ->ofMany(
                ['id' => 'max'],
                fn ($query) => $query->whereNull('ended_at')
            );
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

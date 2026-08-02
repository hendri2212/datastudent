<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $student_count
 * @property int $male_count
 * @property int $female_count
 * @property int $islam_count
 * @property int $kristen_count
 * @property int $katolik_count
 * @property int $hindu_count
 * @property int $buddha_count
 * @property int $khonghucu_count
 */
class Classroom extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Major, $this>
     */
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    /** @return HasManyThrough<Student, StudentEnrollment, $this> */
    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(
            Student::class,
            StudentEnrollment::class,
            'classroom_id',
            'id',
            'id',
            'student_id',
        );
    }

    /** @return HasMany<StudentEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}

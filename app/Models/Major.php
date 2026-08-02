<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Major extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return HasMany<Classroom, $this> */
    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    /** @return HasManyThrough<StudentEnrollment, Classroom, $this> */
    public function enrollments(): HasManyThrough
    {
        return $this->hasManyThrough(
            StudentEnrollment::class,
            Classroom::class,
            'major_id',
            'classroom_id',
            'id',
            'id',
        );
    }
}

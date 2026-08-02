<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class BloodType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /** @return HasManyThrough<Student, StudentHealth, $this> */
    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(
            Student::class,
            StudentHealth::class,
            'blood_type_id',
            'id',
            'id',
            'student_id',
        );
    }

    /**
     * @return HasMany<StudentHealth, $this>
     */
    public function studentHealths(): HasMany
    {
        return $this->hasMany(StudentHealth::class);
    }
}

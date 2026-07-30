<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloodType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany<Student, $this>
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * @return HasMany<StudentHealth, $this>
     */
    public function studentHealths(): HasMany
    {
        return $this->hasMany(StudentHealth::class);
    }
}
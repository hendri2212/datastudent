<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentStatus extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'student_status_id');
    }
}
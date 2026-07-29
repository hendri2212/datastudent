<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentEducationHistory extends Model
{
    use SoftDeletes;

    protected $table = 'student_education_history';

    protected $guarded = ['id'];

    protected $casts = [
        'is_graduated'    => 'boolean',
        'entry_year'      => 'integer',
        'graduation_year' => 'integer',
        'final_score'     => 'float',
    ];

    /**
     * Relasi balik ke Student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi ke EducationLevel
     */
    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }
}
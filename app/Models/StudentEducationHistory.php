<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class StudentEducationHistory extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (StudentEducationHistory $history): void {
            if ($history->certificate && Storage::disk('local')->exists($history->certificate)) {
                Storage::disk('local')->delete($history->certificate);
            }
        });
    }

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
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi ke EducationLevel
     *
     * @return BelongsTo<EducationLevel, $this>
     */
    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }
}

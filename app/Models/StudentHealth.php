<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentHealth extends Model
{
    use SoftDeletes;

    protected $table = 'student_healths';

    protected $fillable = [
    'student_id',
    'height',
    'weight',
    'blood_type_id',
    'allergies',
    'medical_history',
    'disabilities',
    'medications',
    'hospital',
    'doctor',
    'notes',
];

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<BloodType, $this>
     */
    public function bloodType(): BelongsTo
    {
        return $this->belongsTo(BloodType::class);
    }
}
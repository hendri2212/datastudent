<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentSocial extends Model
{
    use SoftDeletes;

    protected $table = 'student_socials';

    protected $guarded = ['id'];

    protected $casts = [
        'is_public'  => 'boolean',
        'is_primary' => 'boolean',
    ];

    /**
     * Relasi balik ke Student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi ke SocialPlatform
     */
    public function socialPlatform(): BelongsTo
    {
        return $this->belongsTo(SocialPlatform::class);
    }
}
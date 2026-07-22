<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'major_id',
        'name',
        'rombel',
        'level',
        'status',
    ];

    /**
     * Relasi ke Jurusan (Setiap Kelas milik 1 Jurusan)
     */
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    /**
     * Relasi ke Siswa (1 Kelas punya banyak Siswa)
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
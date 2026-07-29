<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationLevel extends Model
{
    protected $table = 'education_levels';

    // Matikan timestamps karena tabel migration tidak memilikinya
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Relasi ke Riwayat Pendidikan Siswa
     */
    public function educationHistories(): HasMany
    {
        return $this->hasMany(StudentEducationHistory::class);
    }
}
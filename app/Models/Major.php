<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Major extends Model
{
    use HasFactory;

    // 1. Tentukan nama tabel (Opsional, Laravel otomatis menganggap nama tabelnya 'majors')
    protected $table = 'majors';

    // 2. Daftar kolom yang BISA diisi dari form / controller (PENTING!)
    protected $fillable = [
        'school_id',
        'code',
        'name',
        'status',
    ];

    // 3. Relasi ke Model School (Many to One / BelongsTo)
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // 4. Relasi ke Model Student (One to Many / HasMany)
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
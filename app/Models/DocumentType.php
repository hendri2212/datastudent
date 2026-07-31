<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'is_required', // Rekomendasi kolom jika ada
    ];

    /**
     * @return HasMany<StudentDocument, $this>
     */
    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }
}
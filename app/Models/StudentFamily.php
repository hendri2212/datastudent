<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFamily extends Model
{
    use SoftDeletes;

    /**
     * Nama tabel eksplisit sesuai migrasi database.
     *
     * @var string
     */
    protected $table = 'student_family';

    /**
     * Atribut yang dilindungi dari mass assignment.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Casting tipe data kolom.
     *
     * @var array
     */
    protected $casts = [
        'student_id'                   => 'integer',
        'father_occupation_id'        => 'integer',
        'father_income_category_id'   => 'integer',
        'mother_occupation_id'        => 'integer',
        'mother_income_category_id'   => 'integer',
        'guardian_occupation_id'      => 'integer',
        'guardian_income_category_id' => 'integer',
        'relationship_type_id'        => 'integer',
        'created_at'                  => 'datetime',
        'updated_at'                  => 'datetime',
        'deleted_at'                  => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi Database
    |--------------------------------------------------------------------------
    */

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fatherOccupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class, 'father_occupation_id');
    }

    public function fatherIncomeCategory(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'father_income_category_id');
    }

    public function motherOccupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class, 'mother_occupation_id');
    }

    public function motherIncomeCategory(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'mother_income_category_id');
    }

    public function guardianOccupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class, 'guardian_occupation_id');
    }

    public function guardianIncomeCategory(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'guardian_income_category_id');
    }

    public function relationshipType(): BelongsTo
    {
        return $this->belongsTo(RelationshipType::class, 'relationship_type_id');
    }
}
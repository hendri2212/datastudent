<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Religion extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
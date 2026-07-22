<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $guarded = ['id'];

    /**
     * @return HasMany<Major, $this>
     */
    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }
}
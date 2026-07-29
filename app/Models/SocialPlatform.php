<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPlatform extends Model
{
    protected $table = 'social_platforms';

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Relasi ke akun media sosial siswa
     */
    public function studentSocials(): HasMany
    {
        return $this->hasMany(StudentSocial::class);
    }
}
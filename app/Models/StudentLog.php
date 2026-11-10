<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLog extends Model
{
    protected $guarded = ['id'];

    private const CHANGES_MARKER = "\n[student_changes]";

    public function getDescriptionAttribute(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $markerPosition = strrpos($value, self::CHANGES_MARKER);

        return $markerPosition === false ? $value : substr($value, 0, $markerPosition);
    }

    /** @return array<string, array{old: string|null, new: string|null}>|null */
    public function getChangesAttribute(): ?array
    {
        $description = $this->getRawOriginal('description');
        if (! is_string($description)) {
            return null;
        }

        $markerPosition = strrpos($description, self::CHANGES_MARKER);
        if ($markerPosition === false) {
            return null;
        }

        $decoded = json_decode(substr($description, $markerPosition + strlen(self::CHANGES_MARKER)), true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

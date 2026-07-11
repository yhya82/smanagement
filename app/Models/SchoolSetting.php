<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Single-row table - there is exactly one school per install. No policy-
 * driven create/delete: the row is seeded once (SchoolSettingSeeder) and
 * only ever updated, never created or removed through the app.
 */
class SchoolSetting extends Model
{
    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'email',
        'website',
        'logo_path',
        'midterm_weight',
        'final_weight',
    ];

    public static function current(): self
    {
        // Explicit values, not DB column defaults: firstOrCreate()'s create()
        // path returns the in-memory instance as-is, without re-querying the
        // row, so any column left to a server-side default would come back
        // unset on this object (and null-ing a typed property downstream)
        // rather than actually holding the default it was just given.
        return static::query()->firstOrCreate([], [
            'name' => 'School Management',
            'midterm_weight' => 40,
            'final_weight' => 60,
        ]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? Storage::disk('school-logo')->url($this->logo_path)
            : null;
    }
}

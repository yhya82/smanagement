<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Read on nearly every request in the app, including the public login
     * page for guests - cached rather than a fresh firstOrCreate() query
     * every time, since this row changes at most a few times a year.
     */
    public static function current(): self
    {
        return Cache::remember('school_setting', now()->addHour(), function () {
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
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('school_setting'));
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? Storage::disk('school-logo')->url($this->logo_path)
            : null;
    }
}

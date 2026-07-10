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
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['name' => 'School Management']);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? Storage::disk('school-logo')->url($this->logo_path)
            : null;
    }
}

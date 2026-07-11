<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->using(UserRole::class)
            ->withPivot('scope')
            ->withTimestamps();
    }

    /**
     * Teacher and Student are deliberately excluded: both have dedicated
     * onboarding flows (application approval, teacher onboarding, bulk
     * import) that also create the linked Teacher/Student profile row a
     * bare User assigned through the generic Admin > Users screen
     * wouldn't have. Registrar/Administrator have no such linked table,
     * and neither does any admin-created custom role - so both are safe
     * to assign here.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Role>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Role>
     */
    public function scopeAssignableViaUserManagement($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereIn('name', ['Registrar', 'Administrator'])->orWhere('is_system', false))
            ->orderBy('name');
    }
}

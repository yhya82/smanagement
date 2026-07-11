<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_picture',
        'status',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'must_change_password' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->using(UserRole::class)
            ->withPivot('scope')
            ->withTimestamps();
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Overrides Notifiable's own notifications() (which assumes Laravel's
     * default polymorphic notifications table) - we want ->notify() and its
     * via()/channel extensibility, but our notifications table is the
     * simpler user_id-FK shape from the schema review, not Laravel's
     * default. A class-defined method always wins over one from a trait,
     * so this is a deliberate override, not a naming collision.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Disabling a role (Role::is_active = false) revokes it from every
     * holder immediately without deleting the row or touching foreign
     * keys - both checks filter it out here rather than in every caller.
     */
    public function hasPermission(string $key): bool
    {
        return $this->roles()
            ->where('is_active', true)
            ->whereHas('permissions', fn ($query) => $query->where('key', $key))
            ->exists();
    }

    public function hasRole(string $name): bool
    {
        return $this->roles()->where('is_active', true)->where('name', $name)->exists();
    }

    public function avatarUrl(): ?string
    {
        return $this->profile_picture
            ? Storage::disk('avatars')->url($this->profile_picture)
            : null;
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }

    /**
     * Deletes this user's session rows directly (SESSION_DRIVER=database)
     * rather than relying on Laravel's password-confirmation-based "log out
     * other devices" flow, which only protects the device performing the
     * logout - it can't help an admin locking out a *different* user's
     * already-open session after a suspected-compromise password reset.
     * A deleted session row makes the next request on that session
     * unauthenticated, regardless of which route/endpoint it hits.
     */
    public function invalidateOtherSessions(?string $exceptSessionId = null): void
    {
        if (config('session.driver') !== 'database') {
            // This only ever does something meaningful on the database
            // session driver - failing silently here would mean an admin's
            // "reset this compromised account's password" action reports
            // success while quietly invalidating nothing.
            Log::warning('invalidateOtherSessions() called but SESSION_DRIVER is not database - no sessions were invalidated.', [
                'user_id' => $this->id,
                'session_driver' => config('session.driver'),
            ]);

            return;
        }

        DB::table('sessions')
            ->where('user_id', $this->id)
            ->when($exceptSessionId, fn ($query) => $query->where('id', '!=', $exceptSessionId))
            ->delete();
    }
}

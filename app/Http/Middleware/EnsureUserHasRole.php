<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * First-pass route filter by actor (registered as the `role` alias, used as
 * role:Administrator / role:Registrar / role:Teacher / role:Student - must
 * match the seeded role names exactly, see RoleSeeder). This is deliberately
 * coarse - it just keeps a Teacher-only route out of a Student's reach, for
 * example. The actual per-record scope check (can this teacher see this
 * specific student) is the policies' job, not this middleware's.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! collect($roles)->contains(fn ($role) => $user->hasRole($role))) {
            abort(403);
        }

        return $next($request);
    }
}

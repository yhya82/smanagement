<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every account created through the app's own flows (application approval,
 * teacher onboarding, bulk import, admin-triggered reset) is given a random
 * password the user never sees directly - must_change_password is the only
 * thing that ever forces them onto a screen where they can set one they
 * actually know. Without this, the flag set in those flows was inert.
 */
class EnsureUserHasChangedPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Livewire's own AJAX endpoint (component actions/updates) also runs
        // through the 'web' middleware group - without this exemption, the
        // ChangePassword page's own submit request got redirected before
        // updatePassword() ever ran, so the password never actually changed.
        // Safe to exempt globally: every full page load is still gated below,
        // so the only component that can ever be mounted while this flag is
        // true is ChangePassword itself.
        if ($user && $user->must_change_password
            && ! $request->routeIs('password.change', 'logout', '*livewire.update')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Single post-login landing route (Fortify's `home` config points here)
 * that redirects to the actual role-specific dashboard - keeps the
 * role -> destination mapping in one place rather than scattered across
 * every place something might redirect a freshly-authenticated user.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->hasRole('Administrator')) {
            return redirect()->route('admin.applications.index');
        }

        if ($user->hasRole('Registrar')) {
            return redirect()->route('registrar.applications.index');
        }

        // Teacher/Student dashboards aren't built yet (out of scope for this
        // slice of Phase 11) - a placeholder avoids a login<->dashboard
        // redirect loop for those roles rather than bouncing them to /login
        // while already authenticated.
        return response()->view('dashboard-placeholder');
    }
}

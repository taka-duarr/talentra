<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Allows content admins and super admins. Pass "super" to require a super admin.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $level = null): Response
    {
        $user = $request->user();

        abort_unless($user !== null && $user->isAdmin(), 403);

        if ($level === 'super') {
            abort_unless($user->isSuperAdmin(), 403, 'Halaman ini khusus super admin.');
        }

        return $next($request);
    }
}

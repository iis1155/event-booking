<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    use ApiResponse;

    /**
     * Usage in routes:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,organizer')   ← multiple roles allowed
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return $this->errorResponse(
                message: 'Unauthenticated.',
                code: 401
            );
        }

        $userRole = $request->user()->role->value;

        if (!in_array($userRole, $roles)) {
            return $this->errorResponse(
                message: 'Forbidden. You do not have permission to access this resource.',
                code: 403
            );
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        switch (true) {
            case $user->admin == 1:
            case $user->assessor == 1:
            case $user->p_escola == 1:
            case $user->coordenador == 1:
            case $user->coord_nig == 1:
            case $user->secretaria == 1:
                return $next($request);
            default:
                return response()->json(['error' => 'Unauthorized'], 403);
        }
    }
}

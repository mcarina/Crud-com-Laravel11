<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lógica para restringir o acesso com base no papel do usuário
        {
            $user = $request->user();
            if (! $user || $user->admin !== 1) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return $next($request);
        }

    }
}

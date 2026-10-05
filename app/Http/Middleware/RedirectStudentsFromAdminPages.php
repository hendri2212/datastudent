<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStudentsFromAdminPages
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $role = $user ? $user->role->value : null;

        if ($user && in_array($role, ['student', 'siswa'], true)) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();
        $role = null;

        if ($user) {
            $role = $user->role;
            if ($role instanceof \App\Enums\UserRole) {
                $role = $role->value;
            }
        }

        if ($role === 'student') {
            return $request->wantsJson()
                ? response()->json(['two_factor' => false])
                : redirect()->route('home');
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(config('fortify.home'));
    }
}

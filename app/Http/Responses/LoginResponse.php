<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        // Owner Dashboard
        if ($user->hasRole('Owner')) {
            return redirect()->route('admin.dashboard');
        }

        // Coach Dashboard
        if ($user->hasRole('Coach')) {
            return redirect()->route('coach.dashboard');
        }

        // Unknown role - deny access
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors([
                'email' => 'Your account does not have an assigned role.',
            ]);
    }
}
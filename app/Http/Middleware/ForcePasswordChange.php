<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->must_change_password) {
            if (!$request->is('force-password-change') && !$request->is('logout')) {
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}

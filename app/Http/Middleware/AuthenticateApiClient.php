<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'API key required.'], 401);
        }

        $client = Client::active()->where('api_key', $token)->first();

        if (!$client) {
            return response()->json(['error' => 'Invalid or inactive API key.'], 401);
        }

        // Make client available to controllers
        $request->merge(['authenticated_client' => $client]);
        $request->attributes->set('client', $client);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as Middleware;

class SsoAuthenticate extends Middleware
{
    /**
     * Redirect unauthenticated users directly to the main SSO Portal login (Port 3005) or Filament Login.
     */
    protected function redirectTo($request): ?string
    {
        if (env('SSO_BYPASS', true)) {
            return route('filament.admin.auth.login');
        }

        $host = is_object($request) && method_exists($request, 'getHost') ? ($request->getHost() ?: '127.0.0.1') : '127.0.0.1';
        return "http://{$host}:3005";
    }
}

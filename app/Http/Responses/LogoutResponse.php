<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        try {
            // Delete Passport Tokens in SSO
            $userId = Auth::id() ?? $request->user()?->id;
            
            if ($userId) {
                DB::connection('sso')
                    ->table('oauth_access_tokens')
                    ->where('user_id', $userId)
                    ->delete();
            }
        } catch (\Exception $e) {
            // Lanjutkan redirect meskipun pembersihan gagal
        }

        return redirect()->away('http://localhost:3000/auth/sign-in');
    }
}

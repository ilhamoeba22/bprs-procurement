<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class SsoHandoffController extends Controller
{
    /**
     * Handle Handoff token from SSO Server and log in user seamlessly.
     * GET /admin/sso-login?token=...
     */
    public function handleHandoff(Request $request)
    {
        $token = $request->query('token');
        $host = $request->getHost() ?: '127.0.0.1';

        if (!$token) {
            return redirect()->to("http://{$host}:3005");
        }

        try {
            // Fast verify handoff token with sso_server on port 8000
            $response = Http::timeout(3)->withHeaders(['Accept' => 'application/json'])->post("http://127.0.0.1:8000/api/sso/verify-handoff", [
                'token' => $token,
            ]);

            if ($response->successful() && $response->json('success')) {
                $userData = $response->json('data.user');
                $accessToken = $response->json('data.access_token');

                if (!$userData || empty($userData['nik_user'])) {
                    return redirect()->to("http://{$host}:3005")->with('error', 'Data SSO tidak valid');
                }

                $nik = trim($userData['nik_user']);

                // Find or create local user record in bprs_procurement database
                $user = User::where('nik_user', $nik)->first();

                if (!$user) {
                    $user = User::create([
                        'nama_user' => $userData['nama_user'] ?? $nik,
                        'nik_user'  => $nik,
                        'password'  => bcrypt('sso-authenticated-pass'),
                        'is_active' => true,
                    ]);
                } else {
                    $user->update([
                        'nama_user' => $userData['nama_user'] ?? $user->nama_user,
                        'is_active' => true,
                    ]);
                }

                // Sync roles from SSO server if available
                if (!empty($userData['roles']) && is_array($userData['roles']) && method_exists($user, 'syncRoles')) {
                    try {
                        $user->syncRoles($userData['roles']);
                    } catch (\Exception $e) {
                        // ignore role sync error if spatie permission role doesn't exist locally
                    }
                }

                // Log in to Procurement session
                Auth::login($user, true);
                session(['sso_token' => $accessToken]);

                return redirect()->to('/admin');
            }
        } catch (\Exception $e) {
            logger()->error('SSO Handoff Error in Procurement: ' . $e->getMessage());
        }

        return redirect()->to("http://{$host}:3005")->with('error', 'Autentikasi SSO gagal');
    }

    /**
     * Single Logout from Procurement:
     * 1. Revokes SSO server token
     * 2. Clears local procurement session
     * 3. Redirects main SSO tab to Sign In & closes current Procurement tab automatically!
     * GET|POST /admin/sso-logout
     */
    public function logout(Request $request)
    {
        $host = $request->getHost() ?: '127.0.0.1';
        $ssoToken = session('sso_token');

        if ($ssoToken) {
            try {
                Http::timeout(3)->withToken($ssoToken)->post('http://127.0.0.1:8000/api/sso/logout');
            } catch (\Exception $e) {
                // Ignore timeout
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $ssoSignInUrl = "http://{$host}:3005/auth/sign-in";

        // HTML/JS response to handle cross-tab logout & auto-close tab
        return response("
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <title>Single Logout - BPRS HIK MCI</title>
                <style>
                    body { background: #0b1437; color: #ffffff; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                    .card { text-align: center; background: rgba(255,255,255,0.05); padding: 40px; border-radius: 20px; backdrop-filter: blur(10px); }
                    .title { font-size: 20px; font-weight: bold; margin-bottom: 8px; }
                    .subtitle { color: #a3edcd; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class='card'>
                    <div class='title'>Single Logout Berhasil</div>
                    <div class='subtitle'>Sesi telah diakhiri. Mengalihkan ke SSO Portal...</div>
                </div>
                <script>
                    try {
                        localStorage.removeItem('sso_token');
                        localStorage.removeItem('sso_user');
                    } catch(e){}

                    setTimeout(function() {
                        if (window.opener && !window.opener.closed) {
                            try {
                                window.opener.location.href = '{$ssoSignInUrl}';
                                window.opener.focus();
                            } catch(err){}
                            window.close();
                        } else {
                            window.location.href = '{$ssoSignInUrl}';
                        }
                    }, 500);
                </script>
            </body>
            </html>
        ", 200, ['Content-Type' => 'text/html']);
    }
}

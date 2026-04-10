<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApprovalVerificationController;
use App\Http\Controllers\FileAccessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/verify/approval/{pengajuan}/{user}', [ApprovalVerificationController::class, 'show'])
    ->name('approval.verify')
    ->middleware('signed');


Route::get('/private-files/{path}', [FileAccessController::class, 'show'])
    ->where('path', '.*')
    ->name('private.file')
    ->middleware('auth');

Route::get('/admin/sso-login', function (Request $request) {
    if (!$request->has('token')) {
        return redirect('/admin/login')->withErrors(['nik_user' => 'Invalid SSO Token.']);
    }

    $tokenRecord = DB::connection('sso')
        ->table('sso_auth_tokens')
        ->where('token', $request->token)
        ->first();

    if (!$tokenRecord || now()->greaterThan($tokenRecord->expires_at)) {
        return redirect('/admin/login')->withErrors(['nik_user' => 'Token kadaluarsa atau tidak valid.']);
    }

    // Force Login menggunakan ID
    Auth::loginUsingId($tokenRecord->user_id);

    // Hapus token setelah dipakai (One-Time)
    DB::connection('sso')
        ->table('sso_auth_tokens')
        ->where('token', $request->token)
        ->delete();

    return redirect('/admin');
})->name('sso.login');

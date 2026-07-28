<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApprovalVerificationController;
use App\Http\Controllers\FileAccessController;
use App\Http\Controllers\SsoHandoffController;

// SSO Handoff Authentication Routes (Publicly accessible, supports GET & POST for logout)
Route::get('/admin/sso-login', [SsoHandoffController::class, 'handleHandoff'])->name('sso.login');
Route::match(['get', 'post'], '/admin/sso-logout', [SsoHandoffController::class, 'logout'])->name('sso.logout');

// Redirect web root directly to Filament admin panel
Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/verify/approval/{pengajuan}/{user}', [ApprovalVerificationController::class, 'show'])
    ->name('approval.verify')
    ->middleware('signed');

Route::get('/private-files/{path}', [FileAccessController::class, 'show'])
    ->where('path', '.*')
    ->name('private.file')
    ->middleware('auth');

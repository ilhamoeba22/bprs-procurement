<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApprovalVerificationController;
use App\Http\Controllers\FileAccessController;

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

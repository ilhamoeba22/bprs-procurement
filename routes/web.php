<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApprovalVerificationController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verify/approval/{pengajuan}/{user}', [ApprovalVerificationController::class, 'show'])
    ->name('approval.verify')
    ->middleware('signed');

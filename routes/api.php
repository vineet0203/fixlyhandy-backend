<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\VerificationController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('auth/login/google', [VerificationController::class, 'loginWithGoogle']);
Route::post('auth/login/whatsapp', [VerificationController::class, 'loginWithWhatsapp']);

// Protected verification routes
Route::middleware(['auth:api,customer.jwt,employee.jwt'])->group(function () {
    Route::post('auth/verify/email/send', [VerificationController::class, 'sendEmailOtp']);
    Route::post('auth/verify/email/verify', [VerificationController::class, 'verifyEmailOtp']);
    Route::post('auth/verify/sms/send', [VerificationController::class, 'sendSmsOtp']);
    Route::post('auth/verify/sms/verify', [VerificationController::class, 'verifySmsOtp']);
    Route::post('auth/verify/google', [VerificationController::class, 'verifyGoogle']);
    Route::post('auth/verify/whatsapp/send', [VerificationController::class, 'sendWhatsappOtp']);
    Route::post('auth/verify/whatsapp/verify', [VerificationController::class, 'verifyWhatsappOtp']);
});
